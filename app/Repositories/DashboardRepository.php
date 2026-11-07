<?php
namespace App\Repositories;
use App\Models\ActivityLog;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\KgbHistory;
use App\Models\Notification;
use App\Models\Pppk;
use App\Models\PromotionHistory;
use App\Models\Rank;
use App\Models\User;
use Carbon\Carbon;
class DashboardRepository
{
    private function unitKerja(User $user)
    {
        return $user->kode_unit_kerja;
    }
    private function employeeQuery(?User $user = null)
    {
        $query = Employee::query();
        if (!$user) {
            return $query;
        }
        if ($user->role === 'super_admin') {
            return $query;
        }
        if ($user->role === 'admin') {
            if (!$user->kode_unit_kerja) {
                return $query->whereRaw('1 = 0');
            }
            return $query->where('kode_unit_kerja',$user->kode_unit_kerja);
        }
        return $query->whereRaw('1 = 0');
    }
    public function pegawaiPensiun(?User $user=null)
    {
        return $this->employeeQuery($user)->where('status', 'Pensiun')->count();
    }
    public function pegawaiAktif(?User $user=null)
    {
        return $this->employeeQuery($user)->where('status', 'Aktif')->count();
    }
    public function pegawaiNonaktif(?User $user=null)
    {
        return $this->employeeQuery($user)->where('status', 'Nonaktif')->count();
    }
    public function totalPNS(?User $user=null)
    {
        return $this->employeeQuery($user)->where('status_pegawai', 'PNS')->where('status', 'Aktif')->count();
    }
    public function totalPPPK(?User $user=null)
    {
        return $this->employeeQuery($user)->where('status_pegawai', 'PPPK')->where('status', 'Aktif')->count();
    }
    public function naikPangkat(?User $user=null)
    {
        return $this->employeeQuery($user)->where('status', 'Aktif')->where('status_pegawai', 'PNS')->whereNotNull('tmt_pangkat_baru')
            ->whereBetween('tmt_pangkat_baru',[now(), now()->copy()->addMonths(2)])->count();
    }
    public function kgb(?User $user=null)
    {
        return $this->employeeQuery($user)->where('status', 'Aktif')->whereNotNull('tmt_kgb_baru')
            ->whereBetween('tmt_kgb_baru',[now(), now()->copy()->addMonths(2)])->count();
    }
    public function pensiun(?User $user=null)
    {
        return $this->employeeQuery($user)->where('status', 'Aktif')
            ->whereNotNull('tmt_pensiun')->whereBetween('tmt_pensiun',[now(), now()->copy()->addMonths(6)])->count();
    }
    public function aktivitas(?User $user = null)
    {
        if (!$user) {
            return collect();
        }
        return ActivityLog::with('user')
            ->when($user->role === 'admin', function ($query) use ($user) {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('kode_unit_kerja', $this->unitKerja($user));
                });
            })->latest()->take(6)->get();
    }
    public function notifikasi(?User $user = null)
    {
        if (!$user) {
            return collect();
        }
        return Notification::with('employee')
            ->when($user->role === 'admin', function ($query) use ($user) {
                $query->whereHas('employee', function ($q) use ($user) {
                    $q->where('kode_unit_kerja', $this->unitKerja($user));
                });
            })->latest()->take(5)->get();
    }
    public function chartJenisPegawai(?User $user=null): array
    {
        return [
            'PNS'   => $this->employeeQuery($user)->where('status_pegawai', 'PNS')->where('status', 'Aktif')->count(),
            'PPPK'  => $this->employeeQuery($user)->where('status_pegawai', 'PPPK')->where('status', 'Aktif')->count(),
        ];
    }
    /**
     * Chart Jenis Kelamin
     */
    public function ChartJenisKelamin(?User $user=null): array
    {
        return [
            'Laki-laki' => $this->employeeQuery($user)->where('jenis_kelamin', 'Laki-laki')->where('status', 'Aktif')->count(),
            'Perempuan' => $this->employeeQuery($user)->where('jenis_kelamin', 'Perempuan')->where('status', 'Aktif')->count(),
        ];
    }
    public function chartReminder(int $year, ?User $user=null): array
    {
        $bulan = [
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
        ];
        $pangkat = [];
        $kgb = [];
        $pensiun = [];
        foreach (range(1, 12) as $month) {
            $pangkat[] = $this->employeeQuery($user)->whereYear('tmt_pangkat_baru', $year)
                ->whereMonth('tmt_pangkat_baru', $month)->where('status', 'Aktif')->count();
            $kgb[] = $this->employeeQuery($user)->whereYear('tmt_kgb_baru', $year)
                ->whereMonth('tmt_kgb_baru', $month)->where('status', 'Aktif')->count();
            $pensiun[] = $this->employeeQuery($user)->whereYear('tmt_pensiun', $year)
                ->whereMonth('tmt_pensiun', $month)->where('status', 'Aktif')->count();
        }
        $totalPangkat = array_sum($pangkat);
        $totalKgb = array_sum($kgb);
        $totalPensiun = array_sum($pensiun);
        // Pegawai unik yang memiliki minimal satu reminder
        $pegawaiReminder = $this->employeeQuery($user)->where(function ($q) use ($year) {
            $q->whereYear('tmt_pangkat_baru', $year)->orWhereYear('tmt_kgb_baru', $year)->orWhereYear('tmt_pensiun', $year);
        })->count();
        return [
            'labels' => $bulan,
            'pangkat' => $pangkat,
            'kgb' => $kgb,
            'pensiun' => $pensiun,
            'total_pangkat' => $totalPangkat,
            'total_kgb' => $totalKgb,
            'total_pensiun' => $totalPensiun,
            // tambahan
            'pegawai_reminder' => $pegawaiReminder,
            'total_event' => $totalPangkat + $totalKgb + $totalPensiun,
        ];
    }
    public function chartPangkat(?User $user = null): array
    {
        return Rank::query()
            ->where('kode', '!=', '0')
            ->where('urutan_pangkat', '!=', 0)
            ->withCount([
                'employees as employees_count' => function ($query) use ($user) {
                    $query->where('status', 'Aktif')
                        ->where('status_pegawai', 'PNS');
                    if (!$user || $user->role === 'super_admin') {
                        return;
                    }
                    if ($user->role === 'admin' && $user->kode_unit_kerja) {
                        $query->where(
                            'kode_unit_kerja',
                            $user->kode_unit_kerja
                        );
                        return;
                    }
                    $query->whereRaw('1 = 0');
                },
            ])
            ->orderBy('urutan_pangkat')
            ->get()
            ->map(fn ($rank) => [
                'label' => $rank->nama,
                'value' => $rank->employees_count,
            ])
            ->toArray();
    }
    public function chartGolonganPppk(?User $user = null): array
    {
        return Pppk::query()
            ->where('kode', '!=', '0')
            ->where('urutan_golongan', '!=', 0)
            ->withCount([
                'employees as employees_count' => function ($query) use ($user) {
                    $query->where('status', 'Aktif')
                        ->where('status_pegawai', 'PPPK');
                    if (!$user || $user->role === 'super_admin') {
                        return;
                    }
                    if ($user->role === 'admin' && $user->kode_unit_kerja) {
                        $query->where(
                            'kode_unit_kerja',
                            $user->kode_unit_kerja
                        );
                        return;
                    }
                    $query->whereRaw('1 = 0');
                },
            ])
            ->orderBy('urutan_golongan')
            ->get()
            ->map(fn ($golongan) => [
                'label' => $golongan->nama,
                'value' => $golongan->employees_count,
            ])
            ->toArray();
    }
    public function chartPendidikan(?User $user = null): array
    {
        $educationLevels = EducationLevel::query()->where('status', true)
            ->orderByRaw("
                CASE kode
                    WHEN 'SD' THEN 1
                    WHEN 'SMP' THEN 2
                    WHEN 'SMP_KEJURUAN' THEN 3
                    WHEN 'SMA' THEN 4
                    WHEN 'D1' THEN 5
                    WHEN 'D2' THEN 6
                    WHEN 'SGPLB' THEN 7
                    WHEN 'D3' THEN 8
                    WHEN 'D4' THEN 9
                    WHEN 'S1' THEN 10
                    WHEN 'S2' THEN 11
                    WHEN 'S3' THEN 12
                    ELSE 99
                END
            ")
            ->get(['kode', 'nama']);
        $employeeCounts = $this->employeeQuery($user)->where('employees.status', 'Aktif')
            ->join(
                'education_levels',
                'employees.tingkat_pendidikan',
                '=',
                'education_levels.kode'
            )->where('education_levels.status', true)
            ->selectRaw(
                'employees.tingkat_pendidikan, COUNT(*) as total'
            )
            ->groupBy('employees.tingkat_pendidikan')
            ->pluck('total', 'employees.tingkat_pendidikan');
        return $educationLevels
            ->map(function ($education) use ($employeeCounts) {
                return [
                    'label' => $education->nama,
                    'value' => (int) ($employeeCounts[$education->kode] ?? 0),
                ];
            })->values()->toArray();
    }
    public function chartStatus(?User $user=null): array
    {
        $status = $this->employeeQuery($user)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        return [
            [
                'label' => 'Aktif',
                'value' => $status['Aktif'] ?? 0,
            ],
            [
                'label' => 'Nonaktif',
                'value' => $status['Nonaktif'] ?? 0,
            ],
            [
                'label' => 'Pensiun',
                'value' => $status['Pensiun'] ?? 0,
            ],
        ];
    }
    public function chartMasaKerja(?User $user = null): array
    {
        $pegawai = $this->employeeQuery($user)
            ->where('status', 'Aktif')
            ->whereNotNull('tanggal_masuk')
            ->whereDate('tanggal_masuk', '<=', today())
            ->get(['tanggal_masuk']);
        $result = [
            '0 - 5 Tahun'  => 0,
            '6 - 10 Tahun' => 0,
            '11 - 20 Tahun' => 0,
            '> 20 Tahun'   => 0,
        ];
        foreach ($pegawai as $item) {
            $masaKerja = Carbon::parse($item->tanggal_masuk)
                ->startOfDay()
                ->diffInYears(today());
            if ($masaKerja <= 5) {
                $result['0 - 5 Tahun']++;
            } elseif ($masaKerja <= 10) {
                $result['6 - 10 Tahun']++;
            } elseif ($masaKerja <= 20) {
                $result['11 - 20 Tahun']++;
            } else {
                $result['> 20 Tahun']++;
            }
        }
        return collect($result)
            ->map(fn ($value, $label) => [
                'label' => $label,
                'value' => $value,
            ])
            ->values()
            ->toArray();
    }
    public function chartUsia(?User $user=null): array
    {
        $pegawai = $this->employeeQuery($user)->where('status', 'Aktif')->whereNotNull('tanggal_lahir')->get();
        $result = [
            '20 - 30 Tahun' => 0,
            '31 - 40 Tahun' => 0,
            '41 - 50 Tahun' => 0,
            '> 50 Tahun' => 0,
        ];
        foreach ($pegawai as $item) {
            $usia = Carbon::parse($item->tanggal_lahir)->age;
            if ($usia <= 30) {
                $result['20 - 30 Tahun']++;
            } elseif ($usia <= 40) {
                $result['31 - 40 Tahun']++;
            } elseif ($usia <= 50) {
                $result['41 - 50 Tahun']++;
            } else {
                $result['> 50 Tahun']++;
            }
        }
        return collect($result)
            ->map(fn ($value, $label) => [
                'label' => $label,
                'value' => $value,
            ])
            ->values()
            ->toArray();
    }
    public function chartRiwayat(int $year, ?User $user=null): array
    {
        $bulan = [
            'Jan','Feb','Mar','Apr','Mei','Jun',
            'Jul','Agu','Sep','Okt','Nov','Des'
        ];
        $kgb = [];
        $pangkat = [];
        $pensiun = [];
        foreach (range(1,12) as $month) {
            $kgb[] = KgbHistory::whereYear('tmt_kgb_baru',$year)
                ->when(
                    $user?->role === 'admin',
                    function($query) use($user){
                        $query->whereHas(
                            'employee',
                            function($employee) use($user){
                                $employee->where('kode_unit_kerja',$this->unitKerja($user));
                            }
                        );
                    }
                )->whereMonth('tmt_kgb_baru',$month)->count();
            $pangkat[] = PromotionHistory::whereYear('tmt_pangkat',$year)
                ->when(
                    $user?->role === 'admin',
                    function($query) use($user){
                        $query->whereHas(
                            'employee',
                            function($employee) use($user){
                                $employee->where('kode_unit_kerja',$this->unitKerja($user));
                            }
                        );
                    }
                )->whereMonth('tmt_pangkat',$month)->count();
            $pensiun[] = $this->employeeQuery($user)->whereYear('tmt_pensiun',$year)->whereMonth('tmt_pensiun',$month)->count();
        }
        $totalKgb = array_sum($kgb);
        $totalPangkat = array_sum($pangkat);
        $totalPensiun = array_sum($pensiun);
        $totalEvent = $totalKgb + $totalPangkat + $totalPensiun;
        // Pegawai yang melakukan KGB
        $pegawaiKgb = KgbHistory::whereYear('tmt_kgb_baru',$year)
            ->when(
                $user?->role === 'admin',
                function($query) use($user){
                    $query->whereHas('employee',function($q) use($user){
                        $q->where('kode_unit_kerja',$this->unitKerja($user));
                    });
                }
            )->pluck('nip');
        // Pegawai yang naik pangkat
        $pegawaiPangkat = PromotionHistory::whereYear('tmt_pangkat', $year)
            ->when(
                $user?->role === 'admin',
                function($query) use($user){
                    $query->whereHas(
                        'employee',
                        function($q) use($user){
                            $q->where('kode_unit_kerja',$this->unitKerja($user));
                        }
                    );
                }
            )->pluck('nip');
        // Pegawai yang pensiun
        $pegawaiPensiun = $this->employeeQuery($user)->whereYear('tmt_pensiun', $year)
            ->pluck('nip');
        // Gabungkan lalu hilangkan duplikat
        $pegawaiRiwayat = $pegawaiKgb->merge($pegawaiPangkat)->merge($pegawaiPensiun)->unique()->count();
        return [
            'labels' => $bulan,
            'kgb' => $kgb,
            'pangkat' => $pangkat,
            'pensiun' => $pensiun,
            'total_kgb' => $totalKgb,
            'total_pangkat' => $totalPangkat,
            'total_pensiun' => $totalPensiun,
            'pegawai_riwayat' => $pegawaiRiwayat,
            'total_event' => $totalEvent,
        ];
    }
    public function availableYears(?User $user=null): array
    {
        $pangkat = $this->employeeQuery($user)
            ->selectRaw("EXTRACT(YEAR FROM tmt_pangkat_baru) AS year")->whereNotNull('tmt_pangkat_baru');
        $kgb = $this->employeeQuery($user)
            ->selectRaw("EXTRACT(YEAR FROM tmt_kgb_baru) AS year")->whereNotNull('tmt_kgb_baru');
        $pensiun = $this->employeeQuery($user)
            ->selectRaw("EXTRACT(YEAR FROM tmt_pensiun) AS year")->whereNotNull('tmt_pensiun');
        return $pangkat->union($kgb)->union($pensiun)->pluck('year')->map(fn($year)=>(int)$year)->unique()->sortDesc()->values()->toArray();
    }
    private function getNotificationAge($tanggalNotifikasi): string
    {
        if (empty($tanggalNotifikasi)) {
            return 'Tanggal tidak tersedia';
        }
        $tanggalNotifikasi = Carbon::parse($tanggalNotifikasi)->startOfDay();
        $today = Carbon::today();
        // Jika tanggal notifikasi masih di masa depan
        if ($tanggalNotifikasi->gt($today)) {
            return 'Akan datang';
        }
        // Jika hari ini
        if ($tanggalNotifikasi->equalTo($today)) {
            return 'Hari Ini';
        }
        // Hitung selisih kalender
        $interval = $tanggalNotifikasi->diff($today);
        $parts = [];
        if ($interval->y > 0) {
            $parts[] = $interval->y . ' tahun';
        }
        if ($interval->m > 0) {
            $parts[] = $interval->m . ' bulan';
        }
        if ($interval->d > 0) {
            $parts[] = $interval->d . ' hari';
        }
        return implode(' ', $parts) . ' yang lalu';
    }
    /**
     * Notifikasi yang berkaitan dengan pegawai pemilik akun.
     */
    public function notifikasiPengguna(User $user)
    {
        if (empty($user->nip)) {
            return collect();
        }
        return Notification::with('employee')->where('nip', $user->nip)->latest()->take(5)->get();
    }
    /**
     * Aktivitas yang dilakukan oleh akun pengguna yang login.
     */
    public function aktivitasPengguna(User $user)
    {
        return ActivityLog::with('user.employee')->where('id_user', $user->id)->latest()->take(6)->get();
    }
}

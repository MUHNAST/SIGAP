<?php
namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\DashboardRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
class DashboardService
{
    public function __construct(
        private DashboardRepository $dashboard_repository
    ) {}
    public function getAdminDashboardData(User $user,?int $tahunReminder = null,?int $tahunRiwayat = null): array
    {
        $tahunReminder ??= now()->year;
        $tahunRiwayat ??= now()->year;
        return [
            'tahunReminder'     => $tahunReminder,
            'tahunRiwayat'      => $tahunRiwayat,
            'tahunList'         => $this->dashboard_repository->availableYears($user),
            'pegawaiPensiun'    => $this->dashboard_repository->pegawaiPensiun($user),
            'pegawaiAktif'      => $this->dashboard_repository->pegawaiAktif($user),
            'pegawaiNonaktif'   => $this->dashboard_repository->pegawaiNonaktif($user),
            'totalPNS'          => $this->dashboard_repository->totalPNS($user),
            'totalPPPK'         => $this->dashboard_repository->totalPPPK($user),
            'naikPangkat'       => $this->dashboard_repository->naikPangkat($user),
            'kgb'               => $this->dashboard_repository->kgb($user),
            'pensiun'           => $this->dashboard_repository->pensiun($user),
            'aktivitas'         => $this->dashboard_repository->aktivitas($user),
            'notifikasi'        => $this->dashboard_repository->notifikasi($user),
            'chartJenisPegawai' => $this->dashboard_repository->chartJenisPegawai($user),
            'ChartJenisKelamin'       => $this->dashboard_repository->ChartJenisKelamin($user),
            'chartReminder'     => $this->dashboard_repository->chartReminder($tahunReminder, $user),
            'chartRiwayat'      => $this->dashboard_repository->chartRiwayat($tahunRiwayat, $user),
            'chartPangkat'      => $this->dashboard_repository->chartPangkat($user),
            'chartGolonganPppk'   => $this->dashboard_repository->chartGolonganPppk($user),
            'chartPendidikan'   => $this->dashboard_repository->chartPendidikan($user),
            'chartStatus'       => $this->dashboard_repository->chartStatus($user),
            'chartMasaKerja'    => $this->dashboard_repository->chartMasaKerja($user),
            'chartUsia'         => $this->dashboard_repository->chartUsia($user),
        ];
    }
    public function getPublicDashboardData(?int $tahunReminder = null,?int $tahunRiwayat = null): array {
        $tahunReminder ??= now()->year;
        $tahunRiwayat ??= now()->year;
        $cacheKey = "public_dashboard_{$tahunReminder}_{$tahunRiwayat}";
        return Cache::remember( $cacheKey, now()->addMinutes(15),
            function () use ($tahunReminder, $tahunRiwayat) {
                return [
                    'tahunReminder'     => $tahunReminder,
                    'tahunRiwayat'      => $tahunRiwayat,
                    'tahunList'         => $this->dashboard_repository->availableYears(),
                    'pegawaiPensiun'   => $this->dashboard_repository->pegawaiPensiun(),
                    'pegawaiAktif'     => $this->dashboard_repository->pegawaiAktif(),
                    'pegawaiNonaktif'  => $this->dashboard_repository->pegawaiNonaktif(),
                    'totalPNS'          => $this->dashboard_repository->totalPNS(),
                    'totalPPPK'         => $this->dashboard_repository->totalPPPK(),
                    /*
                    |--------------------------------------------------------------------------
                    | STATISTIK REMINDER
                    |--------------------------------------------------------------------------
                    */
                    'naikPangkat'       => $this->dashboard_repository->naikPangkat(),
                    'kgb'               => $this->dashboard_repository->kgb(),
                    'pensiun'           => $this->dashboard_repository->pensiun(),
                    /*
                    |--------------------------------------------------------------------------
                    | CHART
                    |--------------------------------------------------------------------------
                    */
                    'chartJenisPegawai' => $this->dashboard_repository->chartJenisPegawai(),
                    'ChartJenisKelamin'       => $this->dashboard_repository->ChartJenisKelamin(),
                    'chartReminder'     => $this->dashboard_repository->chartReminder($tahunReminder),
                    'chartRiwayat'      => $this->dashboard_repository->chartRiwayat($tahunRiwayat),
                    'chartPangkat'      => $this->dashboard_repository->chartPangkat(),
                    'chartGolonganPppk'   => $this->dashboard_repository->chartGolonganPppk(),
                    'chartPendidikan'   => $this->dashboard_repository->chartPendidikan(),
                    'chartStatus'       => $this->dashboard_repository->chartStatus(),
                    'chartMasaKerja'    => $this->dashboard_repository->chartMasaKerja(),
                    'chartUsia'         => $this->dashboard_repository->chartUsia(),
                    /*
                    |--------------------------------------------------------------------------
                    | DATA PUBLIC
                    |--------------------------------------------------------------------------
                    */
                    'aktivitas'         => collect(),
                    'notifikasi'        => collect(),
                ];
            }
        );
    }
    public function getUserDashboardData(User $user): array
    {
        $employee = Employee::query()
            ->where('nip', $user->nip)
            ->first();

        return [
            'employee' => $employee,

            // Samakan nama variabel dengan Blade.
            'kgb' => $this->formatSchedule($employee?->tmt_kgb_baru),
            'promotion' => $this->formatSchedule(
                $employee?->status_pegawai === 'PNS'
                    ? $employee->tmt_pangkat_baru
                    : null
            ),
            'retirement' => $this->formatSchedule($employee?->tmt_pensiun),

            // Data khusus pengguna yang sedang login.
            'notifikasi' => $this->dashboard_repository->notifikasiPengguna($user),
            'aktivitas' => $this->dashboard_repository->aktivitasPengguna($user),
        ];
    }

    /**
     * Menyiapkan informasi jadwal untuk dashboard pengguna.
     */
    private function formatSchedule($tanggal): array
    {
        $tanggal = $tanggal
            ? Carbon::parse($tanggal)->startOfDay()
            : null;

        $hariIni = Carbon::today();

        if (!$tanggal) {
            return [
                'tanggal' => null,
                'status' => 'Belum dijadwalkan',
                'tahun_sisa' => 0,
                'bulan_sisa' => 0,
                'hari_sisa' => 0,
            ];
        }

        if ($tanggal->lt($hariIni)) {
            return [
                'tanggal' => $tanggal,
                'status' => 'Terlambat',
                'tahun_sisa' => 0,
                'bulan_sisa' => 0,
                'hari_sisa' => 0,
            ];
        }

        if ($tanggal->equalTo($hariIni)) {
            return [
                'tanggal' => $tanggal,
                'status' => 'Hari Ini',
                'tahun_sisa' => 0,
                'bulan_sisa' => 0,
                'hari_sisa' => 0,
            ];
        }

        $selisih = $hariIni->diff($tanggal);

        return [
            'tanggal' => $tanggal,
            'status' => 'Menunggu',
            'tahun_sisa' => $selisih->y,
            'bulan_sisa' => $selisih->m,
            'hari_sisa' => $selisih->d,
        ];
    }
}

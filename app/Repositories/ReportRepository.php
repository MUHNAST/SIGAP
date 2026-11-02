<?php
namespace App\Repositories;
use App\Models\Employee;
use App\Models\KgbHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
class ReportRepository
{
    protected array $employeeRelations = [
        'position',
        'department',
        'rank',
        'province',
        'city',
        'district',
        'urbanVillage',
        'birthplace',
        'latestKgbHistory',
        'kgbHistories',
    ];
    protected array $historyRelations = [
        'employee',
        'employee.position',
        'employee.department',
        'employee.rank',
        'employee.province',
        'employee.city',
        'employee.district',
        'employee.urbanVillage',
        'employee.birthplace',
        'user',
    ];
    // Mengambil daftar pegawai PNS yang memenuhi jadwal KGB.
    public function getLaporanKGB(
        array $filter = [],
        User $user
    ): Collection {
        $today = Carbon::today();
        $batasKgb = $today->copy()->addMonthsNoOverflow(2);
        $query = Employee::with($this->employeeRelations)
            ->whereNotNull('tanggal_masuk')
            ->where('status_pegawai', 'PNS')
            ->whereNotNull('tmt_kgb_baru')
            ->whereDate('tmt_kgb_baru', '>=', $today)
            ->whereDate('tmt_kgb_baru', '<=', $batasKgb)
            ->whereDoesntHave('kgbHistories', function ($q) {
                $q->whereColumn(
                    'kgb_histories.tmt_kgb_baru',
                    'employees.tmt_kgb_baru'
                );
            });
        $this->filterEmployeeByRole($query, $user);
        if (! empty($filter['search'])) {
            $search = trim($filter['search']);
            $query->where(function ($q) use ($search) {
                $q->where('employees.nama', 'LIKE', "%{$search}%")
                    ->orWhere('employees.nip', 'LIKE', "%{$search}%");
            });
        }
        if (! empty($filter['department'])) {
            $query->where(
                'employees.kode_unit_kerja',
                $filter['department']
            );
        }
        return $query
            ->orderBy('employees.tmt_kgb_baru')
            ->get();
    }
    // Mengambil laporan KGB khusus User yang sedang login.
    public function getLaporanKGBForUser(
        User $user,
        array $filter = []
    ): Collection {
        abort_unless(
            $user->role === 'user' && filled($user->nip),
            403,
            'Akun tidak memiliki akses ke laporan KGB.'
        );
        return $this->getLaporanKGB($filter, $user);
    }
    // Mengambil riwayat KGB berdasarkan NIP dan periode dengan pembatasan akses.
    public function getExistingKgbHistory(
        string $nip,
        Carbon|string $tmtKgbBaru,
        User $user
    ): ?KgbHistory {
        $query = KgbHistory::query()
            ->where('nip', $nip)
            ->whereDate(
                'tmt_kgb_baru',
                Carbon::parse($tmtKgbBaru)->toDateString()
            );
        $this->filterHistoryByRole($query, $user);
        return $query
            ->latest('id')
            ->first();
    }
    // Mengambil pegawai yang memenuhi syarat preview surat KGB.
    // public function getEmployeeForKgbPreview(
    //     string $nip,
    //     User $user
    // ): Employee {
    //     $today = Carbon::today();
    //     $batasKgb = $today->copy()->addMonthsNoOverflow(2);
    //     $query = Employee::with($this->employeeRelations)
    //         ->where('status_pegawai', 'PNS')
    //         ->whereNotNull('tanggal_masuk')
    //         ->whereNotNull('tmt_kgb_baru')
    //         ->whereDate('tmt_kgb_baru', '>=', $today)
    //         ->whereDate('tmt_kgb_baru', '<=', $batasKgb)
    //         ->whereDoesntHave('kgbHistories', function ($q) {
    //             $q->whereColumn(
    //                 'kgb_histories.tmt_kgb_baru',
    //                 'employees.tmt_kgb_baru'
    //             );
    //         });
    //     $this->filterEmployeeByRole($query, $user);
    //     return $query
    //         ->where('employees.nip', $nip)
    //         ->firstOrFail();
    // }
    public function getEmployeeForKgbPreview(
        string $nip,
        User $user
    ): Employee {
        $query = Employee::with($this->employeeRelations);
        $this->filterEmployeeByRole($query, $user);
        return $query
            ->where('employees.nip', $nip)
            ->firstOrFail();
    }
    // Mengambil data Camat sebagai pejabat penandatangan.
    public function getCamat(): ?Employee
    {
        return Employee::with([
            'position',
            'rank',
        ])
            ->where('status', 'Aktif')
            ->whereHas('position', function ($q) {
                $q->whereRaw(
                    'LOWER(nama) LIKE ?',
                    ['%camat%']
                );
            })
            ->first();
    }
    // Mengambil data pegawai berdasarkan NIP dan hak akses pengguna.
    public function getEmployeeByNip(
        string $nip,
        User $user
    ): Employee {
        $query = Employee::with($this->employeeRelations);
        $this->filterEmployeeByRole($query, $user);
        return $query
            ->where('employees.nip', $nip)
            ->firstOrFail();
    }
    // Mengambil riwayat KGB terbaru untuk setiap pegawai.
    public function getHistory(User $user): Collection
    {
        $latestHistory = KgbHistory::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('nip');
        $query = KgbHistory::with($this->historyRelations)
            ->whereIn('id', $latestHistory)
            ->latest('tmt_kgb_baru');
        $this->filterHistoryByRole($query, $user);
        return $query->get();
    }
    // Mengambil detail riwayat KGB berdasarkan ID.
    public function getHistoryById(
        int $id,
        User $user
    ): KgbHistory {
        $query = KgbHistory::with($this->historyRelations);
        $this->filterHistoryByRole($query, $user);
        return $query
            ->whereKey($id)
            ->firstOrFail();
    }
    // Mengambil riwayat KGB sebelum periode tertentu.
    public function getPreviousHistory(
        string $nip,
        User $user,
        ?string $beforeTmt = null,
        ?int $excludeHistoryId = null
    ): ?KgbHistory {
        $query = KgbHistory::query()
            ->with($this->historyRelations)
            ->where('nip', $nip);
        if ($beforeTmt !== null) {
            $query->whereDate(
                'tmt_kgb_baru',
                '<',
                Carbon::parse($beforeTmt)->toDateString()
            );
        }
        if ($excludeHistoryId !== null) {
            $query->where('id', '<>', $excludeHistoryId);
        }
        $this->filterHistoryByRole($query, $user);
        return $query
            ->orderByDesc('tmt_kgb_baru')
            ->orderByDesc('id')
            ->first();
    }
    // Membatasi akses pegawai berdasarkan peran.
    protected function filterEmployeeByRole(
        $query,
        User $user
    ): void {
        if ($user->role === 'super_admin') {
            return;
        }
        if ($user->role === 'admin') {
            if (blank($user->kode_unit_kerja)) {
                $query->whereRaw('1 = 0');
                return;
            }
            $query->where(
                'employees.kode_unit_kerja',
                $user->kode_unit_kerja
            );
            return;
        }
        if ($user->role === 'user') {
            if (blank($user->nip)) {
                $query->whereRaw('1 = 0');
                return;
            }
            $query->where(
                'employees.nip',
                $user->nip
            );
            return;
        }
        // Role tidak dikenal tidak mendapatkan akses.
        $query->whereRaw('1 = 0');
    }
    // Membatasi akses riwayat KGB berdasarkan peran.
    protected function filterHistoryByRole(
        $query,
        User $user
    ): void {
        if ($user->role === 'super_admin') {
            return;
        }
        if ($user->role === 'admin') {
            if (blank($user->kode_unit_kerja)) {
                $query->whereRaw('1 = 0');
                return;
            }
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where(
                    'employees.kode_unit_kerja',
                    $user->kode_unit_kerja
                );
            });
            return;
        }
        if ($user->role === 'user') {
            if (blank($user->nip)) {
                $query->whereRaw('1 = 0');
                return;
            }
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where(
                    'employees.nip',
                    $user->nip
                );
            });
            return;
        }
        // Role tidak dikenal tidak mendapatkan akses.
        $query->whereRaw('1 = 0');
    }
}

<?php
namespace App\Services;
use App\Models\User;
use App\Repositories\ActivityLogRepository;
use Illuminate\Support\Facades\Auth;
class ActivityLogService
{
    protected ActivityLogRepository $activity_log_repository;
    public function __construct(
        ActivityLogRepository $activity_log_repository
    ) {
        $this->activity_log_repository = $activity_log_repository;
    }
    private function authenticatedUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User,401,'Silakan login terlebih dahulu.');
        return $user;
    }
    public function authorizeUser(User $user): void
    {
        if (!$this->canAccessUser($user)) {
            abort(403, 'Anda tidak memiliki akses.');
        }
    }
    private function unitKerjaId(User $user): String
    {
        abort_if(
                empty($user->kode_unit_kerja),
                403,
                'Akun belum memiliki Unit Kerja.'
            );
            return (string) $user->kode_unit_kerja;
        }
    public function getAll(array $columns = ['*'])
    {
        $user = $this->authenticatedUser();
        if ($user->role === 'super_admin') {
            return $this->activity_log_repository->getAll($columns);
        }
        return $this->activity_log_repository->getFiltered(
            [],
            null,
            $this->unitKerjaId($user)
        );
    }
    public function getByUserId(int $idUser,array $filters = []) {
        $user = $this->authenticatedUser();
        if ($user->role === 'super_admin') {
            return $this->activity_log_repository->getFiltered($filters,$idUser);
        }
        return $this->activity_log_repository->getFiltered(
            $filters,
            $idUser,
            $this->unitKerjaId($user)
        );
    }
    public function create(array $data)
    {
        return $this->activity_log_repository->create($data);
    }
    public function getFiltered(array $filters = [])
    {
        $user = $this->authenticatedUser();
        if ($user->role === 'super_admin') {
            return $this->activity_log_repository->getFiltered($filters);
        }
        return $this->activity_log_repository->getFiltered($filters, null, $this->unitKerjaId($user));
    }
    // public function getDistinctActivities()
    // {
    //     $user = $this->authenticatedUser();
    //     $activities = $user->role === 'super_admin'
    //         ? $this->activity_log_repository->getDistinctActivities()
    //         : $this->activity_log_repository->getDistinctActivities($this->unitKerjaId($user));
    //     return $activities->map(function (string $activity) {
    //             $prefixes = [
    //                 'Tambah Data',
    //                 'Update Data',
    //                 'Hapus Data',
    //                 'Import Data',
    //                 'Export Data',
    //                 'Download Template',
    //                 'Reset Password',
    //                 'Reset Username',
    //                 'Ubah Role',
    //                 'Update Profil',
    //             ];
    //             foreach ($prefixes as $prefix) {
    //                 if (str_starts_with($activity, $prefix)) {
    //                     return $prefix;
    //                 }
    //             }
    //             return $activity;
    //         })->unique()->sort()->values();
    // }
    public function getDistinctActivities()
    {
        $user = $this->authenticatedUser();
        $activities = $user->role === 'super_admin'
            ? $this->activity_log_repository->getDistinctActivities() : $this->activity_log_repository->getDistinctActivities($this->unitKerjaId($user));
        $activityGroups = [
            'Tambah Data' => ['Tambah Data','Tambah Pengguna',],
            'Update Data' => ['Update Data','Update Pengguna','Update Profil','Ubah Role', 'Update Foto Profil', 'Update Password'],
            'Hapus Data' => ['Hapus Data','Hapus Pengguna',],
            'Import Data' => ['Import Data',],
            'Export Data' => ['Export Data',],
            'Download Template' => ['Download Template',],
            'Reset' => ['Reset Password', 'Reset Foto Profil', 'Reset Username'],
            'Logout Perangkat' => [ 'Logout Perangkat', 'Logout Semua Perangkat', 'Logout'],
            'Login' => ['Login'],
        ];
        return $activities
            ->map(function (string $activity) use ($activityGroups) {
                foreach ($activityGroups as $group => $patterns) {
                    foreach ($patterns as $pattern) {
                        if (str_starts_with($activity, $pattern)) {
                            return $group;
                        }
                    }
                }
                return null;
            })->filter()->unique()->sort()->values();
    }
    public function getStatistics(): array
    {
        $user = $this->authenticatedUser();
        if ($user->role === 'super_admin') {
            return [
                'totalAktivitas' => $this->activity_log_repository->countAll(),
                'aktivitasHariIni' => $this->activity_log_repository->countToday(),
                'penggunaAktifHariIni' => $this->activity_log_repository->countActiveUsersToday(),
                // 'perubahanData' => $this->activity_log_repository->countPerubahanData(),
            ];
        }
        $idUnitKerja = $this->unitKerjaId($user);
        return [
            'totalAktivitas' => $this->activity_log_repository->countByDepartment($idUnitKerja),
            'aktivitasHariIni' => $this->activity_log_repository->countTodayByDepartment($idUnitKerja),
            'penggunaAktifHariIni' => $this->activity_log_repository->countActiveUsersTodayByDepartment($idUnitKerja),
            // 'perubahanData' => $this->activity_log_repository->countPerubahanDataByDepartment($idUnitKerja),
        ];
    }
    public function canAccessUser(User $targetUser): bool
    {
        $loginUser = $this->authenticatedUser();
        if ($loginUser->role === 'super_admin') {
            return true;
        }
        $loginUnitKerja = $this->unitKerjaId($loginUser);
        $targetUnitKerja = $targetUser->kode_unit_kerja;
        if (is_null($targetUnitKerja)) {
            return false;
        }
        return (string) $loginUnitKerja === (string) $targetUnitKerja;
    }
    public function log(string $aktivitas,?string $deskripsi) {
        // $user = Auth::user();
        $user = $this->authenticatedUser();
        return $this->create([
            'id_user'    => $user->id,
            'aktivitas'  => $aktivitas,
            'deskripsi'  => $deskripsi,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

<?php
namespace App\Repositories;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
class ActivityLogRepository
{
    protected ActivityLog $activityLog;
    public function __construct(ActivityLog $activityLog)
    {
        $this->activityLog = $activityLog;
    }
    private function withRelations(Builder $query): Builder
    {
        return $query->with([
            'user:id,nama,kode_unit_kerja',
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | Operator pencarian MySQL dan PostgreSQL
    |--------------------------------------------------------------------------
    */
    private function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
    }
    private function byDepartment(string $kodeUnitKerja): Builder
    {
        return $this->activityLog->newQuery()
            ->whereHas('user', function (Builder $query) use ($kodeUnitKerja) {
                $query->where('kode_unit_kerja', $kodeUnitKerja);
            });
    }
    private function applyPerubahanDataFilter(Builder $query): Builder {
        $like = $this->likeOperator();
        return $query->where(
            function (Builder $query) use ($like) {
                $query
                    ->where('aktivitas', $like, 'Tambah Data%')
                    ->orWhere('aktivitas', $like, 'Update Data%')
                    ->orWhere('aktivitas', $like, 'Hapus Data%')
                    ->orWhere('aktivitas', $like, 'Import Data%')
                    ->orWhere('aktivitas', $like, 'Export Data%')
                    ->orWhere('aktivitas',$like,'Download Template%')
                    ->orWhere('aktivitas',$like,'Reset Password%')
                    ->orWhere('aktivitas',$like,'Reset Username%')
                    ->orWhere('aktivitas', $like, 'Ubah Role%')
                    ->orWhere('aktivitas',$like,'Update Profil%');
            }
        );
    }
    public function getAll(array $data = ['*'])
    {
        $query = $this->activityLog->newQuery()->select($data);
        return $this->withRelations($query)->latest()->get();
    }
    public function getByUserId(int $idUser)
    {
        $query = $this->activityLog->newQuery()->where('id_user', $idUser);
        return $this->withRelations($query)->latest()->get();
    }
    public function create(array $data): ActivityLog
    {
        return $this->activityLog->create($data);
    }
    public function getFiltered(array $filters = [],?int $idUser = null,?string $KodeUnitKerja = null) {
        $like = $this->likeOperator();
        $query = $this->withRelations($this->activityLog->newQuery());
        if (!is_null($idUser)) {
            $query->where('id_user', $idUser);
        }
        if (!is_null($KodeUnitKerja)) {
            $query->whereHas('user', function (Builder $query) use ($KodeUnitKerja) {
                $query->where('kode_unit_kerja', $KodeUnitKerja);
            });
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(
                function (Builder $query) use ($search, $like) {
                    $query
                        ->where('aktivitas',$like,"%{$search}%")
                        ->orWhere('deskripsi',$like,"%{$search}%")
                        ->orWhereHas('user', function (Builder $query) use ($search, $like) {
                        $query->where('nama', $like, "%{$search}%");
                    });
                }
            );
        }
        if (!empty($filters['aktivitas'])) {
            $aktivitas = trim($filters['aktivitas']);
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
             if (isset($activityGroups[$aktivitas])) {
                $query->where(function (Builder $query) use ( $activityGroups, $aktivitas, $like ) {
                    foreach ($activityGroups[$aktivitas] as $index => $pattern) {
                        if ($index === 0) {
                            $query->where('aktivitas', $like, $pattern);
                        } else {
                            $query->orWhere('aktivitas', $like, $pattern);
                        }
                    }
                });
            } else {
                $query->where('aktivitas', $like, "{$aktivitas}%");
            }
        }
        if (!empty($filters['tanggal'])) {
            $query->whereDate('created_at',$filters['tanggal']);
        }
        return $query->latest()->paginate(config('app.pagination'))->withQueryString();
    }
    public function getDistinctActivities(?string $KodeUnitKerja = null) {
        $query = $this->activityLog->newQuery();
        if (!is_null($KodeUnitKerja)) {
            $query->whereHas('user', function (Builder $query) use ($KodeUnitKerja) {
                $query->where('kode_unit_kerja', $KodeUnitKerja);
            });
        }
        return $query->whereNotNull('aktivitas')->select('aktivitas')->distinct()->orderBy('aktivitas')->pluck('aktivitas');
    }
    public function countByDepartment(string $KodeUnitKerja): string {
        return $this->byDepartment($KodeUnitKerja)->count();
    }
    public function countTodayByDepartment(string $KodeUnitKerja): string {
        return $this->byDepartment($KodeUnitKerja)->whereDate('created_at', today())->count();
    }
    public function countActiveUsersTodayByDepartment(string $KodeUnitKerja): string {
        return $this->byDepartment($KodeUnitKerja)->whereDate('created_at', today())->whereNotNull('id_user')->distinct()->count('id_user');
    }
    public function countPerubahanDataByDepartment(string $KodeUnitKerja): string {
        return $this->applyPerubahanDataFilter($this->byDepartment($KodeUnitKerja))->count();
    }
    public function countAll(): int
    {
        return $this->activityLog->newQuery()->count();
    }
    public function countToday(): int
    {
        return $this->activityLog->newQuery()->whereDate('created_at', today())->count();
    }
    public function countActiveUsersToday(): int
    {
        return $this->activityLog->newQuery()->whereDate('created_at', today())->whereNotNull('id_user')->distinct()->count('id_user');
    }
    public function countPerubahanData(): int
    {
        return $this->applyPerubahanDataFilter($this->activityLog->newQuery())->count();
    }
}

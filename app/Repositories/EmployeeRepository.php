<?php
namespace App\Repositories;
use App\Models\City;
use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Pppk;
use App\Models\Province;
use App\Models\Rank;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
class EmployeeRepository
{
    protected array $relations = [
        'position',
        'rank',
        'department',
        'province',
        'city',
        'district',
        'urbanVillage',
        'birthplace',
    ];
    /**
     * Memastikan pengguna memiliki hak akses pengelolaan pegawai.
     * Hanya Super Admin dan Admin yang diperbolehkan.
     */
    protected function authorizeEmployeeManagement(User $user): void
    {
        if (!in_array($user->role, ['super_admin', 'admin'], true)) {
            throw new AuthorizationException(
                'Anda tidak memiliki hak akses untuk mengelola data pegawai.'
            );
        }
    }
    /**
     * Memastikan Admin memiliki unit kerja.
     */
    protected function getAdminUnit(User $user): string
    {
        if (
            $user->role !== 'admin'
            || !filled($user->kode_unit_kerja)
        ) {
            throw new AuthorizationException(
                'Unit kerja Admin belum ditentukan.'
            );
        }
        return $user->kode_unit_kerja;
    }
    /**
     * Membatasi data pegawai berdasarkan hak akses pengguna.
     *
     * Super Admin : Seluruh unit kerja.
     * Admin       : Unit kerja sendiri.
     * User        : Data pegawai berdasarkan NIP sendiri.
     */
    protected function employeeQuery(User $user): Builder
    {
        $query = Employee::query();
        if ($user->role === 'super_admin') {
            return $query;
        }
        if ($user->role === 'admin') {
            return $query->where(
                'kode_unit_kerja',
                $this->getAdminUnit($user)
            );
        }
        if ($user->role === 'user') {
            if (!filled($user->nip)) {
                throw new AuthorizationException(
                    'Akun pengguna belum terhubung dengan data pegawai.'
                );
            }
            return $query->where('nip', $user->nip);
        }
        throw new AuthorizationException(
            'Anda tidak memiliki hak akses untuk melihat data pegawai.'
        );
    }
    /**
     * Menampilkan data pegawai sesuai hak akses.
     */
    public function getAll(User $user, array $columns = ['*'])
    {
        return $this->employeeQuery($user)
            ->with($this->relations)
            ->select($columns)
            ->latest()
            ->get();
    }
    /**
     * Mengambil data untuk export.
     * Hanya Super Admin dan Admin.
     */
    public function getExportData(User $user)
    {
        $this->authorizeEmployeeManagement($user);
        return $this->employeeQuery($user)
            ->with($this->relations)
            ->get();
    }
    /**
     * Menampilkan detail pegawai sesuai hak akses.
     */
    public function getById(
        string $nip,
        User $user,
        array $columns = ['*']
    ) {
        if (
            $columns !== ['*']
            && !in_array('nip', $columns, true)
        ) {
            $columns[] = 'nip';
        }
        return $this->employeeQuery($user)
            ->with($this->relations)
            ->select($columns)
            ->where('nip', $nip)
            ->firstOrFail();
    }
    /**
     * Menambahkan data pegawai.
     * Super Admin dapat menambahkan ke seluruh unit.
     * Admin hanya dapat menambahkan ke unit sendiri.
     */
    public function create(array $data, User $user)
    {
        $this->authorizeEmployeeManagement($user);
        if ($user->role === 'admin') {
            $data['kode_unit_kerja'] = $this->getAdminUnit($user);
        }
        return Employee::create($data);
    }
    /**
     * Mengubah data pegawai sesuai hak akses.
     *
     * Super Admin : Dapat mengubah seluruh data pegawai.
     * Admin       : Hanya dapat mengubah pegawai di unit sendiri.
     * User        : Hanya dapat mengubah data dirinya sendiri.
     */
    public function update(string $nip, array $data, User $user)
    {
        if (!in_array(
            $user->role,
            ['super_admin', 'admin', 'user'],
            true
        )) {
            throw new AuthorizationException(
                'Anda tidak memiliki hak akses untuk memperbarui data pegawai.'
            );
        }
        $employee = $this->employeeQuery($user)
            ->where('nip', $nip)
            ->firstOrFail();
        if ($user->role === 'admin') {
            $data['kode_unit_kerja'] = $this->getAdminUnit($user);
        }
        $employee->update($data);
        return $employee->refresh();
    }
    /**
     * Menghapus data pegawai.
     * Hanya Super Admin yang diperbolehkan.
     */
    public function delete(string $nip, User $user)
    {
        if ($user->role !== 'super_admin') {
            throw new AuthorizationException(
                'Hanya Super Admin yang memiliki hak akses untuk menghapus data pegawai.'
            );
        }
        $employee = Employee::query()
            ->where('nip', $nip)
            ->firstOrFail();
        return $employee->delete();
    }
    /**
     * Mengambil data referensi untuk formulir pegawai.
     *
     * Super Admin : Seluruh referensi.
     * Admin       : Referensi unit kerja sendiri.
     * User        : Referensi untuk memperbarui data pribadi.
     */
    public function getMasterData(User $user)
    {
        $departments = match ($user->role) {
            'super_admin', 'user' => Department::orderBy('nama', 'ASC')->get(),
            'admin' => Department::where(
                'kode',
                $this->getAdminUnit($user)
            )->get(),
            default => throw new AuthorizationException(
                'Anda tidak memiliki hak akses untuk mengakses data master pegawai.'
            ),
        };
        return [
            'positions' => Position::orderBy('nama', 'ASC')->get(),
            'ranks' => Rank::orderBy('nama', 'ASC')->get(),
            'pppk' => Pppk::orderBy('nama', 'ASC')->get(),
            'salaries' => Salary::orderBy('id_pangkat', 'ASC')->get(),
            'provinces' => Province::orderBy('nama', 'ASC')->get(),
            'cities' => City::orderBy('nama', 'ASC')->get(),
            'departments' => $departments,
            'districts' => collect(),
            'urbanVillages' => collect(),
            'educationLevels' => EducationLevel::query()
                ->where('status', true)
                ->orderBy('kode')
                ->get(),
        ];
    }
    /**
     * Membuat atau memperbarui data pegawai,
     * termasuk untuk proses import.
     * Hanya Super Admin dan Admin.
     */
    public function updateOrCreate(
        array $attributes,
        array $values,
        User $user
    ) {
        $this->authorizeEmployeeManagement($user);
        $existingEmployee = Employee::query()
            ->where($attributes)
            ->first();
        if (
            $user->role === 'admin'
            && $existingEmployee
            && $existingEmployee->kode_unit_kerja !== $this->getAdminUnit($user)
        ) {
            throw ValidationException::withMessages([
                'nip' => 'Data pegawai tersebut terdaftar pada unit kerja lain.',
            ]);
        }
        if ($user->role === 'admin') {
            $values['kode_unit_kerja'] = $this->getAdminUnit($user);
        }
        return $this->employeeQuery($user)
            ->updateOrCreate($attributes, $values);
    }
    /**
     * Mengambil daftar pangkat berdasarkan tingkat pendidikan
     * dan status pegawai.
     */
    public function getRanksByEducation(
        ?string $educationLevelCode,
        ?string $statusPegawai = null
    ) {
        if (!$educationLevelCode || !$statusPegawai) {
            return collect();
        }
        $education = EducationLevel::query()
            ->where('kode', $educationLevelCode)
            ->where('status', true)
            ->first();
        if (!$education) {
            return collect();
        }
        // Batas golongan PPPK tetap mengikuti database.
        if ($statusPegawai === 'PPPK') {
            if (
                $education->golongan_pppk_minimum === null
                || $education->golongan_pppk_maksimum === null
            ) {
                return collect();
            }
            return Pppk::query()
                ->where('urutan_golongan', '>', 0)
                ->whereBetween('urutan_golongan', [
                    $education->golongan_pppk_minimum,
                    $education->golongan_pppk_maksimum,
                ])
                ->orderBy('urutan_golongan')
                ->get();
        }
        // Batas pangkat PNS disesuaikan berdasarkan pendidikan.
        if ($statusPegawai === 'PNS') {
            if (
                $education->pangkat_pns_minimum === null
                || $education->pangkat_pns_maksimum === null
            ) {
                return collect();
            }

            $maksimumPangkat = (int) $education->pangkat_pns_maksimum;

            // Pendidikan D3 ke atas mendapatkan tambahan satu tingkat.
            if (in_array($educationLevelCode, ['D3', 'D4', 'S1', 'S2', 'S3'], true)) {
                $maksimumPangkat++;
            }

            return Rank::query()
                ->where('urutan_pangkat', '>', 0)
                ->whereBetween('urutan_pangkat', [
                    $education->pangkat_pns_minimum,
                    $maksimumPangkat,
                ])
                ->orderBy('urutan_pangkat')
                ->get();
        }
        return collect();
    }
}

<?php
namespace App\Repositories;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Auth\Access\AuthorizationException;
class UserRepository
{
    /**
     * Query dasar untuk menentukan data pengguna
     * berdasarkan cakupan akses pengguna yang login.
     */
    private function getAuthorizedQuery(User $actor): Builder
    {
        $query = User::query()
            ->with(['department', 'activityLogs']);

        if ($actor->role === 'super_admin') {
            return $query;
        }

        if (
            $actor->role === 'admin' &&
            filled($actor->kode_unit_kerja)
        ) {
            return $query
                ->where('role', 'user')
                ->where('kode_unit_kerja', $actor->kode_unit_kerja);
        }

        return $query->whereRaw('1 = 0');
    }
    /**
     * Mendapatkan seluruh data pengguna sesuai hak akses.
     */
    public function getAll(User $actor, array $columns = ['*'])
    {
        return $this->getAuthorizedQuery($actor)
            ->select($columns)
            ->orderBy('role', 'ASC')
            ->latest()
            ->get();
    }
    /**
     * Mendapatkan detail pengguna sesuai hak akses.
     */
    public function getById(int $id, User $actor): User
    {
        return $this->getAuthorizedQuery($actor)
            ->whereKey($id)
            ->firstOrFail();
    }
    public function getByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }
    public function create(array $data): User
    {
        $storedPhoto = null;
        if (
            isset($data['foto']) &&
            $data['foto'] instanceof UploadedFile
        ) {
            $filename = Str::uuid() . '.' .
                $data['foto']->getClientOriginalExtension();
            $storedPhoto = $data['foto']->storeAs(
                'pengguna',
                $filename,
                'public'
            );
            if (! $storedPhoto) {
                throw new \RuntimeException(
                    'Foto profil gagal disimpan.'
                );
            }
            $data['foto'] = $storedPhoto;
        }
        try {
            return User::create($data);
        } catch (\Throwable $exception) {
            if ($storedPhoto !== null) {
                Storage::disk('public')->delete($storedPhoto);
            }
            throw $exception;
        }
    }
    public function update(User $user, array $data): User
    {
        if (
            isset($data['foto']) &&
            $data['foto'] instanceof UploadedFile
        ) {
            $filename = Str::uuid() . '.' .
                $data['foto']->getClientOriginalExtension();
            $newPhoto = $data['foto']->storeAs(
                'pengguna',
                $filename,
                'public'
            );
            if (! $newPhoto) {
                throw new \RuntimeException(
                    'Foto profil gagal disimpan.'
                );
            }
            $oldPhoto = $user->foto;
            $data['foto'] = $newPhoto;
            try {
                if (! $user->update($data)) {
                    throw new \RuntimeException(
                        'Data pengguna gagal diperbarui.'
                    );
                }
            } catch (\Throwable $exception) {
                Storage::disk('public')->delete($newPhoto);
                throw $exception;
            }
            if (
                filled($oldPhoto) &&
                $oldPhoto !== 'images/default-user.png' &&
                Storage::disk('public')->exists($oldPhoto)
            ) {
                Storage::disk('public')->delete($oldPhoto);
            }
            return $user->fresh();
        }
        $user->update($data);
        return $user->fresh();
    }
    public function delete(User $user): bool
    {
        if (
            filled($user->foto) &&
            $user->foto !== 'images/default-user.png' &&
            Storage::disk('public')->exists($user->foto)
        ) {
            Storage::disk('public')->delete($user->foto);
        }
        return $user->delete();
    }
    /**
     * Memeriksa apakah unit kerja sudah digunakan
     * oleh Admin atau Super Admin.
     */
    public function isUnitUsedByPrivilegedRole(
        string $kode,
        ?int $exceptUserId = null
    ): bool {
        return User::query()
            ->where('kode_unit_kerja', $kode)
            ->whereIn('role', ['admin', 'super_admin'])
            ->when($exceptUserId, function ($query) use ($exceptUserId) {
                $query->where('id', '!=', $exceptUserId);
            })
            ->exists();
    }
    /**
     * Mendapatkan unit kerja yang tersedia untuk Admin.
     */
    public function getAvailableDepartments(?int $exceptUserId = null)
    {
        return Department::query()
            ->where('status', true)
            ->whereNotExists(function ($query) use ($exceptUserId) {
                $query->selectRaw(1)
                    ->from('users')
                    ->whereColumn(
                        'users.kode_unit_kerja',
                        'departments.kode'
                    )
                    ->whereIn('users.role', ['admin', 'super_admin'])
                    ->when($exceptUserId, function ($sub) use ($exceptUserId) {
                        $sub->where('users.id', '!=', $exceptUserId);
                    });
            })
            ->orderBy('nama')
            ->get();
    }
}

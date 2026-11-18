<?php
namespace App\Repositories;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
    /**
     * Menyimpan foto pengguna.
     */
    private function uploadPhoto(UploadedFile $foto): string
    {
        $filename = Str::uuid() . '.' .
            $foto->getClientOriginalExtension();
        $path = $foto->storeAs(
            'pengguna',
            $filename,
            'public'
        );
        if (!$path) {
            throw new \RuntimeException(
                'Foto profil gagal disimpan.'
            );
        }
        return $path;
    }
    /**
     * Menghapus foto pengguna dari penyimpanan.
     */
    private function deletePhoto(?string $photo): void
    {
        if (
            filled($photo) &&
            $photo !== 'images/default-user.png' &&
            Storage::disk('public')->exists($photo)
        ) {
            Storage::disk('public')->delete($photo);
        }
    }
    /**
     * Memeriksa apakah data foto merupakan unggahan baru.
     */
    private function hasNewPhoto(array $data): bool
    {
        return isset($data['foto']) &&
            $data['foto'] instanceof UploadedFile;
    }
    /**
     * Membuat akun pengguna.
     */
    public function create(array $data): User
    {
        $storedPhoto = null;
        if ($this->hasNewPhoto($data)) {
            $storedPhoto = $this->uploadPhoto($data['foto']);
            $data['foto'] = $storedPhoto;
        }
        try {
            return User::create($data);
        } catch (\Throwable $exception) {
            $this->deletePhoto($storedPhoto);
            throw $exception;
        }
    }
    /**
     * Memperbarui data pengguna.
     */
    public function update(User $user, array $data): User
    {
        $storedPhoto = null;
        $oldPhoto = $user->foto;
        if ($this->hasNewPhoto($data)) {
            $storedPhoto = $this->uploadPhoto($data['foto']);
            $data['foto'] = $storedPhoto;
        }
        try {
            if (!$user->update($data)) {
                throw new \RuntimeException(
                    'Data pengguna gagal diperbarui.'
                );
            }
            // Hapus foto lama setelah pembaruan berhasil.
            if ($storedPhoto !== null) {
                $this->deletePhoto($oldPhoto);
            }
            return $user->fresh();
        } catch (\Throwable $exception) {
            // Hapus foto baru jika pembaruan gagal.
            $this->deletePhoto($storedPhoto);
            throw $exception;
        }
    }
    /**
     * Menghapus pengguna beserta foto profilnya.
     */
    public function delete(User $user): bool
    {
        $deleted = $user->delete();
        if ($deleted) {
            $this->deletePhoto($user->foto);
        }
        return $deleted;
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

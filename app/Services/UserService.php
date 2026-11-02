<?php
namespace App\Services;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Access\AuthorizationException;
class UserService
{
    private const DEFAULT_PASSWORD = '123456789';
    public function __construct(
        private UserRepository $user_repository
    ) {}
    /**
     * Mendapatkan pengguna yang sedang login.
     */
    private function getActor(): User
    {
        $actor = Auth::user();
        if (!$actor) {
            throw new AuthorizationException(
                'Anda harus login untuk melakukan tindakan ini.'
            );
        }
        return $actor;
    }
    /**
     * Memastikan pengguna memiliki permission tertentu.
     */
    private function authorizePermission(string $permission): User
    {
        $actor = $this->getActor();
        // Super Admin memiliki akses penuh.
        if ($actor->role === 'super_admin') {
            return $actor;
        }
        // Admin mengikuti konfigurasi Manajemen Hak Akses.
        if (
            $actor->role !== 'admin' ||
            !$actor->hasPermission($permission)
        ) {
            throw new AuthorizationException(
                'Anda tidak memiliki hak akses untuk melakukan tindakan ini.'
            );
        }
        return $actor;
    }
    /**
     * Memastikan pengguna dapat mengakses data target.
     */
    private function authorizeUserAccess(
        User $user,
        string $permission
    ): User {
        $actor = $this->authorizePermission($permission);
        // Super Admin dapat mengakses seluruh pengguna.
        if ($actor->role === 'super_admin') {
            return $actor;
        }
        // Admin hanya dapat mengakses akun User dalam unit kerjanya.
        if (
            $actor->role === 'admin' &&
            $user->role === 'user' &&
            filled($actor->kode_unit_kerja) &&
            $user->kode_unit_kerja === $actor->kode_unit_kerja
        ) {
            return $actor;
        }
        throw new AuthorizationException(
            'Anda tidak memiliki hak akses terhadap data pengguna tersebut.'
        );
    }
    /**
     * Mendapatkan seluruh data pengguna sesuai hak akses.
     */
    public function getAll()
    {
        $actor = $this->authorizePermission('user.view');
        return $this->user_repository->getAll($actor);
    }
    /**
     * Mendapatkan detail pengguna sesuai hak akses.
     */
    public function getById(int $id): User
    {
        $actor = $this->authorizePermission('user.view');
        return $this->user_repository->getById($id, $actor);
    }
    public function findUser(int $id): User
    {
        return $this->getById($id);
    }
    /**
     * Mendapatkan unit kerja yang tersedia.
     * Super Admin dapat melihat seluruh unit kerja.
     * Admin hanya dapat melihat unit kerjanya sendiri.
     */
    public function getAvailableDepartment(?int $exceptUserId = null)
    {
        $actor = $this->getActor();
        if ($actor->role === 'super_admin') {
            return $this->user_repository->getAvailableDepartments(
                $exceptUserId
            );
        }
        if (
            $actor->role === 'admin' &&
            filled($actor->kode_unit_kerja)
        ) {
            return $this->user_repository->getAvailableDepartments(
                $exceptUserId,
                $actor->kode_unit_kerja
            );
        }
        throw new AuthorizationException(
            'Anda tidak memiliki hak akses untuk melihat unit kerja.'
        );
    }
    /**
     * Menghapus pengguna.
     * Hanya Super Admin yang dapat melakukan penghapusan.
     */
    public function delete(User $user): void
    {
        $actor = $this->getActor();
        if ($actor->role !== 'super_admin') {
            throw new AuthorizationException(
                'Anda tidak memiliki hak akses untuk menghapus pengguna.'
            );
        }
        if ($user->role === 'super_admin') {
            throw new \Exception(
                'Akun Super Admin tidak dapat dihapus.'
            );
        }
        if ($actor->id === $user->id) {
            throw new \Exception(
                'Anda tidak dapat menghapus akun sendiri.'
            );
        }
        $this->user_repository->delete($user);
    }
    /**
     * Membuat akun Admin.
     * Hanya Super Admin yang dapat membuat akun.
     */
    public function create(array $data): User
    {
        $actor = $this->getActor();
        if ($actor->role !== 'super_admin') {
            throw new AuthorizationException(
                'Anda tidak memiliki hak akses untuk membuat pengguna.'
            );
        }
        $data['role'] = 'admin';
        if (
            $this->user_repository->isUnitUsedByPrivilegedRole(
                $data['kode_unit_kerja']
            )
        ) {
            throw new \Exception(
                'Unit kerja tersebut sudah digunakan oleh akun Admin atau Super Admin.'
            );
        }
        $data['password'] = Hash::make(self::DEFAULT_PASSWORD);
        return $this->user_repository->create($data);
    }
    /**
     * Memperbarui data pengguna.
     * Hanya Super Admin yang dapat melakukan perubahan.
     */
    public function update(User $user, array $data): User
    {
        $actor = $this->getActor();
        if ($actor->role !== 'super_admin') {
            throw new AuthorizationException(
                'Anda tidak memiliki hak akses untuk mengubah pengguna.'
            );
        }
        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            if (
                $this->user_repository->isUnitUsedByPrivilegedRole(
                    $data['kode_unit_kerja'],
                    $user->id
                )
            ) {
                throw new \Exception(
                    'Unit kerja tersebut sudah digunakan oleh akun Admin atau Super Admin lainnya.'
                );
            }
        }
        $payload = [
            'nama' => $data['nama'],
            'username' => $data['username'],
            'email' => $data['email'],
            'no_hp' => $data['no_hp'],
            'kode_unit_kerja' => $data['kode_unit_kerja'],
        ];
        if (!empty($data['foto'])) {
            $payload['foto'] = $data['foto'];
        }
        return $this->user_repository->update($user, $payload);
    }
    /**
     * Reset password pengguna.
     */
    public function resetPassword(User $user): User
    {
        $this->authorizeUserAccess($user, 'user.reset_password');
        return $this->user_repository->update($user, [
            'password' => Hash::make(self::DEFAULT_PASSWORD),
        ]);
    }
}

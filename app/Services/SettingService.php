<?php
namespace App\Services;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\SettingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
class SettingService
{
    public function __construct(
        private SettingRepository $setting_repository,
        private EmployeeService $employee_service
    ) {}
    public function getLastLogin(int $userId)
    {
        return $this->setting_repository->getLastLogin($userId);
    }
    public function getActivities(int $userId, int $limit = 10)
    {
        return $this->setting_repository->getActivities($userId, $limit);
    }
    public function updateProfile(int $userId, array $data)
    {
        $user = $this->setting_repository->findUserById($userId);
        $this->setting_repository->updateUser($user, $data);
        return $user->fresh();
    }
    public function updatePhoto(Request $request)
    {
        $user = $this->setting_repository->findUserById(Auth::id());
        if (! $request->hasFile('foto')) {
            return $user;
        }
        $employee = $user->employee;
        // Simpan foto baru terlebih dahulu.
        $newPhoto = $this->setting_repository->storePhoto(
            $request->file('foto')
        );
        $oldPhotos = array_filter([
            $user->foto,
            $employee?->foto,
        ], fn ($photo) => $photo && $photo !== 'images/default-user.png');
        try {
            DB::transaction(function () use ($user, $employee, $newPhoto) {
                // Perbarui foto akun pengguna.
                $this->setting_repository->updateUser($user, [
                    'foto' => $newPhoto,
                ]);
                // Perbarui foto pegawai jika memiliki data pegawai.
                if ($employee) {
                    $this->setting_repository->updateEmployeePhoto(
                        $employee,
                        $newPhoto
                    );
                }
            });
        } catch (\Throwable $exception) {
            // Hapus foto baru jika proses penyimpanan database gagal.
            $this->setting_repository->deletePhoto($newPhoto);
            throw $exception;
        }
        // Hapus foto lama setelah kedua data berhasil diperbarui.
        foreach (array_unique($oldPhotos) as $oldPhoto) {
            if ($oldPhoto !== $newPhoto) {
                $this->setting_repository->deletePhoto($oldPhoto);
            }
        }
        return $user->fresh();
    }
    public function resetPhoto(int $userId)
    {
        $user = $this->setting_repository->findUserById($userId);
        $employee = $user->employee;
        $oldPhotos = array_filter([
            $user->foto,
            $employee?->foto,
        ], fn ($photo) => $photo && $photo !== 'images/default-user.png');
        DB::transaction(function () use ($user, $employee) {
            // Kembalikan foto akun ke foto default.
            $this->setting_repository->updateUser($user, [
                'foto' => 'images/default-user.png',
            ]);
            // Kembalikan foto pegawai ke foto default.
            if ($employee) {
                $this->setting_repository->updateEmployeePhoto(
                    $employee,
                    'images/default-user.png'
                );
            }
        });
        // Hapus foto lama setelah proses database berhasil.
        foreach (array_unique($oldPhotos) as $oldPhoto) {
            $this->setting_repository->deletePhoto($oldPhoto);
        }
        return $user->fresh();
    }
    public function updatePassword(array $data): void
    {
        $user = Auth::user();
        if (! $user) {
            throw ValidationException::withMessages([
                'current_password' => 'Sesi login tidak ditemukan. Silakan login kembali.',
            ]);
        }
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password lama tidak sesuai.',
            ]);
        }
        $this->setting_repository->updatePassword(
            $user,
            Hash::make($data['password'])
        );
        Auth::logoutOtherDevices($data['password']);
    }
    public function updateEmployee(Employee $employee, array $data): Employee
    {
        return $this->employee_service->update($employee, $data);
    }
    public function getMasterData(User $user)
    {
        return $this->employee_service->getMasterData($user);
    }
    public function logoutDevices(array $data): void
    {
        $user = Auth::user();
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password yang dimasukkan salah.',
            ]);
        }
        $this->setting_repository->logoutOtherDevices(
            $data['current_password']
        );
        $user->refresh();
        $this->setting_repository->regenerateRememberToken($user);
    }
    //  Memulai konfigurasi 2FA.
    public function enableTwoFactor(array $data): void
    {
        $user = Auth::user();
        if (! $user) {
            throw ValidationException::withMessages([
                'current_password' => 'Sesi login tidak ditemukan. Silakan login kembali.',
            ]);
        }
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password yang dimasukkan salah.',
            ]);
        }
        $user->refresh();
        if ($user->email_2fa_enabled) {
            throw ValidationException::withMessages([
                'current_password' => 'Verifikasi dua langkah sudah aktif.',
            ]);
        }
        if (blank($user->email)) {
            throw ValidationException::withMessages([
                'current_password' => 'Email akun belum tersedia. Lengkapi email terlebih dahulu.',
            ]);
        }
        $user->forceFill([
            'email_2fa_enabled' => true,
        ])->save();
    }
    /**
     * Menonaktifkan 2FA menggunakan password saat ini.
     */
    public function disableTwoFactor(array $data): void
    {
        $user = Auth::user();
        if (! $user) {
            throw ValidationException::withMessages([
                'current_password' => 'Sesi login tidak ditemukan. Silakan login kembali.',
            ]);
        }
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password yang dimasukkan salah.',
            ]);
        }
        $user->refresh();
        if (! $user->email_2fa_enabled) {
            throw ValidationException::withMessages([
                'current_password' => 'Verifikasi dua langkah sudah nonaktif.',
            ]);
        }
        $user->forceFill([
            'email_2fa_enabled' => false,
        ])->save();
    }
}

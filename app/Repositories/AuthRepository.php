<?php
namespace App\Repositories;
use App\Models\LoginDevice;
use App\Models\User;
class AuthRepository
{
    /**
     * Mengambil akun berdasarkan ID.
     */
    public function getUserById(int $id): ?User
    {
        return User::find($id);
    }
    /**
     * Mengambil perangkat berdasarkan user dan fingerprint.
     */
    public function getDeviceByFingerprint(
        int $userId,
        string $fingerprint
    ): ?LoginDevice {
        return LoginDevice::where('id_user', $userId)
            ->where('device_fingerprint', $fingerprint)
            ->first();
    }
    /**
     * Menghitung jumlah perangkat yang terdaftar.
     */
    public function countUserDevices(int $userId): int
    {
        return LoginDevice::where('id_user', $userId)->count();
    }
    /**
     * Menyimpan atau memperbarui perangkat login.
     */
    public function updateOrCreateDevice(
        int $userId,
        string $fingerprint,
        array $data
    ): LoginDevice {
        return LoginDevice::updateOrCreate(
            [
                'id_user' => $userId,
                'device_fingerprint' => $fingerprint,
            ],
            $data
        );
    }
    /**
     * Menghapus perangkat berdasarkan session ID.
     */
    public function deleteDeviceBySession(string $sessionId): int
    {
        return LoginDevice::where('session_id', $sessionId)->delete();
    }
}

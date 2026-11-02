<?php
namespace App\Repositories;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class SettingRepository
{
    public function findUserById(int $id): User
    {
        return User::findOrFail($id);
    }
    public function updateUser(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }
    public function storePhoto($file): string
    {
        return $file->store('pengguna', 'public');
    }
    public function deletePhoto(string $path): void
    {
        if (!$path || $path === 'images/default-user.png') {
            return;
        }
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
    public function updateEmployeePhoto(Employee $employee, string $path): Employee
    {
        $employee->update([
            'foto' => $path,
        ]);

        return $employee->fresh();
    }
    public function updatePassword(User $user, string $password): bool
    {
        return $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();
    }
    public function logoutOtherDevices(string $password): void
    {
        Auth::logoutOtherDevices($password);
    }
    public function regenerateRememberToken(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();
    }
    public function deleteRememberToken(int $userId): void
    {
        DB::table('users')->where('id', $userId)->update(['remember_token' => null]);
    }
    public function getLastLogin(int $userId)
    {
        return ActivityLog::where('id_user', $userId)->where('aktivitas', 'Login')->latest()->first();
    }
    public function getActivities(int $userId, int $limit = 5)
    {
        return ActivityLog::where('id_user', $userId)->latest()->limit($limit)->get();
    }
}

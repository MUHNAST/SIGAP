<?php

namespace Tests\Unit\Repositories;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\SettingRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private SettingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new SettingRepository();
    }

    public function test_user_lookup_update_and_photo_storage_use_public_disk(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['nama' => 'Before']);
        $path = $this->repository->storePhoto(
            UploadedFile::fake()->create('avatar.jpg', 10, 'image/jpeg')
        );

        $this->assertSame($user->id, $this->repository->findUserById($user->id)->id);
        $this->assertStringStartsWith('pengguna/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_profile_and_employee_photo_updates_persist_and_return_fresh_models(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['nama' => 'Before']);
        $storedPath = $this->repository->storePhoto(
            UploadedFile::fake()->create('avatar.jpg', 10, 'image/jpeg')
        );
        $this->assertStringStartsWith('pengguna/', $storedPath);
        Storage::disk('public')->assertExists($storedPath);

        $updatedUser = $this->repository->updateUser($user, ['nama' => 'After']);
        $employee = Employee::create([
            'nip' => '198501012010011001',
            'nama' => 'Pegawai',
        ]);
        $updatedEmployee = $this->repository->updateEmployeePhoto($employee, $storedPath);

        $this->assertSame('After', $updatedUser->nama);
        $this->assertSame($storedPath, $updatedEmployee->foto);
        $this->assertSame($updatedEmployee->nip, $employee->fresh()->nip);
    }

    public function test_photo_deletion_ignores_default_and_missing_files_and_removes_existing_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pengguna/old.jpg', 'old image');

        $this->repository->deletePhoto('');
        $this->repository->deletePhoto('images/default-user.png');
        $this->repository->deletePhoto('pengguna/missing.jpg');
        Storage::disk('public')->assertExists('pengguna/old.jpg');

        $this->repository->deletePhoto('pengguna/old.jpg');
        Storage::disk('public')->assertMissing('pengguna/old.jpg');
    }

    public function test_password_tokens_and_other_device_logout_are_delegated(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->repository->updatePassword($user, 'new-password-123'));
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->repository->regenerateRememberToken($user);
        $this->assertNotEmpty($user->fresh()->remember_token);

        $this->repository->deleteRememberToken($user->id);
        $this->assertNull($user->fresh()->remember_token);
        Auth::shouldReceive('logoutOtherDevices')->once()->with('new-password-123');
        $this->repository->logoutOtherDevices('new-password-123');
    }

    public function test_activity_queries_return_latest_login_and_requested_limit(): void
    {
        $user = User::factory()->create();
        $first = ActivityLog::create([
            'id_user' => $user->id,
            'aktivitas' => 'Login',
            'created_at' => now()->subMinutes(2),
        ]);
        $latest = ActivityLog::create([
            'id_user' => $user->id,
            'aktivitas' => 'Login',
            'created_at' => now()->subMinute(),
        ]);
        $other = ActivityLog::create([
            'id_user' => $user->id,
            'aktivitas' => 'Update Profil',
        ]);

        $this->assertSame($latest->id, $this->repository->getLastLogin($user->id)->id);
        $this->assertSame([$other->id, $latest->id], $this->repository->getActivities($user->id, 2)->pluck('id')->all());
    }

    public function test_missing_user_lookup_throws_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->repository->findUserById(999999);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Services\ActivityLogService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SettingsFailureHandlingTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        $department = Department::create([
            'kode' => 'SETTINGS-FAIL',
            'nama' => 'Unit Pengujian Error',
            'status' => true,
        ]);

        return User::factory()->create([
            'role' => 'super_admin',
            'nama' => 'Admin Pengujian',
            'kode_unit_kerja' => $department->kode,
        ]);
    }

    public function test_photo_reset_failure_does_not_expose_exception_details(): void
    {
        $user = $this->createSuperAdmin();
        $settingService = Mockery::mock(SettingService::class);
        $settingService->shouldReceive('getLastLogin')->once()->with($user->id)->andReturn(null);
        $settingService->shouldReceive('getActivities')->once()->with($user->id)->andReturn(collect());
        $settingService->shouldReceive('resetPhoto')
            ->once()
            ->with($user->id)
            ->andThrow(new RuntimeException('database host and query details'));

        $this->app->instance(SettingService::class, $settingService);
        $this->app->instance(
            ActivityLogService::class,
            Mockery::mock(ActivityLogService::class)
        );

        Log::spy();

        $response = $this->followingRedirects()
            ->actingAs($user)
            ->from(route('setting.index'))
            ->delete(route('setting.photo.reset'));

        $response->assertOk()
            ->assertSee('Foto profil gagal direset. Silakan coba kembali.')
            ->assertDontSee('database host and query details')
            ->assertSee('modalResetFoto');
        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Failed to reset profile photo.',
                Mockery::on(fn (array $context): bool => $context === [
                    'user_id' => $user->id,
                    'exception_type' => RuntimeException::class,
                ])
            );

    }

    public function test_logout_devices_failure_does_not_expose_exception_details(): void
    {
        $user = $this->createSuperAdmin();
        $settingService = Mockery::mock(SettingService::class);
        $settingService->shouldReceive('getLastLogin')->once()->with($user->id)->andReturn(null);
        $settingService->shouldReceive('getActivities')->once()->with($user->id)->andReturn(collect());
        $settingService->shouldReceive('logoutDevices')
            ->once()
            ->with(['current_password' => 'valid-password'])
            ->andThrow(new RuntimeException('database host and query details'));

        $this->app->instance(SettingService::class, $settingService);
        $this->app->instance(
            ActivityLogService::class,
            Mockery::mock(ActivityLogService::class)
        );

        Log::spy();

        $response = $this->followingRedirects()
            ->actingAs($user)
            ->from(route('setting.index'))
            ->post(route('setting.logout.devices'), [
                'current_password' => 'valid-password',
            ]);

        $response->assertOk()
            ->assertSee('Perangkat lain gagal dikeluarkan. Silakan coba kembali.')
            ->assertDontSee('database host and query details')
            ->assertSee('modalLogoutDevices');
        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Failed to log out other devices.',
                Mockery::on(fn (array $context): bool => $context === [
                    'user_id' => $user->id,
                    'exception_type' => RuntimeException::class,
                ])
            );

    }
}

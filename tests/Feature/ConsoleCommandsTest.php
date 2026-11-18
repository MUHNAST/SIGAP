<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LoginDevice;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_hosting_database_check_reports_migration_and_employee_table_status(): void
    {
        $this->artisan('hosting:check-database')
            ->expectsOutputToContain('Tabel employees: ADA')
            ->expectsOutputToContain('Migration create_employees_table: TERCATAT')
            ->assertExitCode(0);
    }

    public function test_clean_login_devices_removes_only_devices_inactive_for_over_one_hour(): void
    {
        $user = User::factory()->create();
        $stale = LoginDevice::create([
            'id_user' => $user->id,
            'device_fingerprint' => 'stale-device',
            'aktivitas_terakhir' => now()->subHours(2),
        ]);
        $recent = LoginDevice::create([
            'id_user' => $user->id,
            'device_fingerprint' => 'recent-device',
            'aktivitas_terakhir' => now()->subMinutes(30),
        ]);

        $this->artisan('app:clean-login-devices')
            ->expectsOutputToContain('1 perangkat login berhasil dibersihkan.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('login_devices', ['id' => $stale->id]);
        $this->assertDatabaseHas('login_devices', ['id' => $recent->id]);
    }

    public function test_notification_and_employee_sync_commands_delegate_to_services(): void
    {
        $notifications = Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('generateNotifications')->once();
        $this->app->instance(NotificationService::class, $notifications);

        $this->artisan('notification:generate')
            ->expectsOutput('Notification generated successfully.')
            ->assertExitCode(0);

        $employees = Mockery::mock(EmployeeService::class);
        $employees->shouldReceive('syncTmt')->once();
        $this->app->instance(EmployeeService::class, $employees);

        $this->artisan('employee:sync-tmt')
            ->expectsOutput('TMT pegawai berhasil disinkronisasi.')
            ->assertExitCode(0);
    }

    public function test_periodic_tmt_command_advances_past_dates_and_preserves_future_dates(): void
    {
        Carbon::setTestNow('2026-11-14 10:00:00');

        try {
            $overdue = Employee::create([
                'nip' => '198501012010011001',
                'nama' => 'Pegawai TMT Lama',
                'tmt_kgb_baru' => '2020-01-01',
                'tmt_pangkat_baru' => '2018-01-01',
            ]);
            $future = Employee::create([
                'nip' => '198501012010011002',
                'nama' => 'Pegawai TMT Mendatang',
                'tmt_kgb_baru' => '2028-01-01',
                'tmt_pangkat_baru' => '2030-01-01',
            ]);

            $this->artisan('employee:update-tmt-periodik')
                ->expectsOutput('Update TMT selesai.')
                ->assertExitCode(0);

            $overdue->refresh();
            $future->refresh();
            $this->assertSame('2026-01-01', $overdue->tmt_kgb_lama->toDateString());
            $this->assertSame('2028-01-01', $overdue->tmt_kgb_baru->toDateString());
            $this->assertSame('2026-01-01', $overdue->tmt_pangkat_lama->toDateString());
            $this->assertSame('2030-01-01', $overdue->tmt_pangkat_baru->toDateString());
            $this->assertNull($future->tmt_kgb_lama);
            $this->assertSame('2028-01-01', $future->tmt_kgb_baru->toDateString());
            $this->assertNull($future->tmt_pangkat_lama);
            $this->assertSame('2030-01-01', $future->tmt_pangkat_baru->toDateString());
        } finally {
            Carbon::setTestNow();
        }
    }
}

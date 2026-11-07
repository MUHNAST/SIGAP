<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Rank;
use App\Models\User;
use App\Jobs\SendEmailJob;
use App\Jobs\SendWhatsappJob;
use Illuminate\Bus\UniqueLock;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createNotificationEmployee(string $nip, string $unit): Employee
    {
        Department::firstOrCreate(
            ['kode' => $unit],
            ['nama' => $unit, 'status' => true]
        );

        return Employee::create([
            'nip' => $nip,
            'nama' => "Pegawai {$nip}",
            'kode_unit_kerja' => $unit,
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
        ]);
    }

    private function addNotification(Employee $employee, string $jenis, bool $read = false): Notification
    {
        return Notification::create([
            'nip' => $employee->nip,
            'jenis' => $jenis,
            'judul' => "Notifikasi {$jenis}",
            'pesan' => "Isi {$jenis}",
            'tanggal_notifikasi' => today()->toDateString(),
            'tanggal_referensi' => today()->addMonth()->toDateString(),
            'sudah_dibaca' => $read,
        ]);
    }

    public function test_admin_notifications_are_limited_to_own_unit_and_statistics(): void
    {
        $ownEmployee = $this->createNotificationEmployee('199001012020011101', 'NOTIF-A');
        $otherEmployee = $this->createNotificationEmployee('199001012020011102', 'NOTIF-B');
        $ownNotification = $this->addNotification($ownEmployee, 'KGB');
        $this->addNotification($ownEmployee, 'Pensiun', true);
        $this->addNotification($otherEmployee, 'Pangkat');
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'NOTIF-A',
        ]);

        $this->actingAs($admin)
            ->get(route('notification.index'))
            ->assertOk()
            ->assertViewHas('data', fn ($data) => $data->total() === 2
                && $data->contains('id', $ownNotification->id))
            ->assertViewHas('statistics', [
                'total' => 2,
                'belumDibaca' => 1,
            ])
            ->assertSee('Sudah Dibaca');

        $this->get(route('notification.unread-count'))
            ->assertOk()
            ->assertJson(['count' => 1]);
    }

    public function test_super_admin_sees_notifications_across_units_and_user_sees_only_own(): void
    {
        $employeeA = $this->createNotificationEmployee('199001012020011103', 'NOTIF-C');
        $employeeB = $this->createNotificationEmployee('199001012020011104', 'NOTIF-D');
        $notificationA = $this->addNotification($employeeA, 'KGB');
        $this->addNotification($employeeB, 'Pangkat');

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('notification.index'))
            ->assertOk()
            ->assertViewHas('data', fn ($data) => $data->total() === 2);

        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $employeeA->nip,
        ]);
        $this->actingAs($user)
            ->get(route('notification.index'))
            ->assertOk()
            ->assertViewIs('notification.index_user')
            ->assertViewHas('data', fn ($data) => $data->total() === 1
                && $data->contains('id', $notificationA->id))
            ->assertViewHas('statistics', [
                'total' => 1,
                'belumDibaca' => 1,
            ]);
    }

    public function test_empty_notification_list_and_latest_collection_are_safe(): void
    {
        $employee = $this->createNotificationEmployee('199001012020011111', 'NOTIF-EMPTY');
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'NOTIF-EMPTY',
        ]);

        $this->actingAs($admin)
            ->get(route('notification.index'))
            ->assertOk()
            ->assertViewHas('data', fn ($data) => $data->total() === 0)
            ->assertSee('Tidak Ada Notifikasi');

        $this->assertSame(
            0,
            app(\App\Services\NotificationService::class)
                ->getLatestNotifications(3)
                ->count()
        );
        $this->assertSame(0, $employee->notifications()->count());
    }

    public function test_category_filter_matches_the_persisted_kgb_processed_kind(): void
    {
        $employee = $this->createNotificationEmployee('199001012020011105', 'NOTIF-E');
        $processed = $this->addNotification($employee, 'KGB Diproses');
        $this->addNotification($employee, 'KGB');
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'NOTIF-E',
        ]);

        $this->actingAs($admin)
            ->get(route('notification.index', ['kategori' => 'KGB Diproses']))
            ->assertOk()
            ->assertViewHas('data', fn ($data) => $data->total() === 1
                && $data->contains('id', $processed->id))
            ->assertSee('value="KGB Diproses"', false);

        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $employee->nip,
        ]);
        $this->actingAs($user)
            ->get(route('notification.index', ['kategori' => 'KGB Diproses']))
            ->assertOk()
            ->assertViewHas('data', fn ($data) => $data->total() === 1
                && $data->contains('id', $processed->id))
            ->assertSee('value="KGB Diproses"', false);
    }

    public function test_read_actions_are_scoped_and_topbar_uses_post(): void
    {
        $employeeA = $this->createNotificationEmployee('199001012020011106', 'NOTIF-F');
        $employeeB = $this->createNotificationEmployee('199001012020011107', 'NOTIF-G');
        $ownNotification = $this->addNotification($employeeA, 'KGB Diproses');
        $otherNotification = $this->addNotification($employeeB, 'KGB');
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'NOTIF-F',
        ]);

        $this->actingAs($admin)
            ->get(route('notification.read.notification', $ownNotification->id))
            ->assertMethodNotAllowed();
        $this->assertFalse($ownNotification->fresh()->sudah_dibaca);

        $this->post(route('notification.read.notification', $ownNotification->id))
            ->assertRedirect(route('notification.index'));
        $this->assertTrue($ownNotification->fresh()->sudah_dibaca);

        $this->put(route('notification.read', $otherNotification->id))
            ->assertForbidden();
        $this->assertFalse($otherNotification->fresh()->sudah_dibaca);

        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $employeeA->nip,
        ]);
        $this->actingAs($user)
            ->put(route('notification.read', $otherNotification->id))
            ->assertForbidden();
        $this->put(route('notification.read-all'))
            ->assertRedirect();
        $this->assertTrue($ownNotification->fresh()->sudah_dibaca);
        $this->assertFalse($otherNotification->fresh()->sudah_dibaca);

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->put(route('notification.read', $otherNotification->id))
            ->assertRedirect();
        $this->assertTrue($otherNotification->fresh()->sudah_dibaca);
    }

    public function test_all_notification_categories_generate_once_using_jakarta_date(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-04 17:30:00', 'UTC'));

        $rank = Rank::create([
            'kode' => 'I/a',
            'nama' => 'Juru Muda',
            'urutan_pangkat' => 1,
        ]);
        EducationLevel::create([
            'kode' => 'SD',
            'nama' => 'SD',
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 12,
        ]);
        $employee = Employee::create([
            'nip' => '199001012020011108',
            'nama' => 'Pegawai Pengingat',
            'status' => 'Aktif',
            'status_pegawai' => 'PNS',
            'tingkat_pendidikan' => 'SD',
            'id_pangkat' => $rank->id,
            'tmt_pangkat_baru' => '2027-01-05',
            'tmt_kgb_baru' => '2027-01-05',
            'tmt_pensiun' => '2027-05-05',
        ]);
        $service = app(NotificationService::class);

        $service->generateNotification($employee);
        $service->generateNotification($employee);

        $this->assertDatabaseCount('notifications', 3);
        foreach ([
            'Pangkat' => '2027-01-05',
            'KGB' => '2027-01-05',
            'Pensiun' => '2027-05-05',
        ] as $jenis => $tanggalReferensi) {
            $this->assertTrue(
                Notification::query()
                    ->where('nip', $employee->nip)
                    ->where('jenis', $jenis)
                    ->whereDate('tanggal_referensi', $tanggalReferensi)
                    ->whereDate('tanggal_notifikasi', '2026-11-05')
                    ->exists()
            );
        }
    }

    public function test_missing_dates_do_not_create_automatic_notifications(): void
    {
        Queue::fake();
        $employee = $this->createNotificationEmployee('199001012020011109', 'NOTIF-H');

        app(NotificationService::class)->generateNotification($employee);

        $this->assertDatabaseCount('notifications', 0);
        Queue::assertNothingPushed();
    }

    public function test_generator_ignores_nonactive_employees_and_preserves_existing_notifications(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-04 12:00:00', 'Asia/Jakarta'));
        $rank = Rank::create([
            'kode' => 'I/b',
            'nama' => 'Juru Muda Tingkat I',
            'urutan_pangkat' => 2,
        ]);
        EducationLevel::create([
            'kode' => 'SD-AKTIF',
            'nama' => 'SD',
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 12,
        ]);

        $employees = [];
        foreach ([
            ['199001012020011116', 'Aktif', 'PNS'],
            ['199001012020011117', 'Nonaktif', 'PNS'],
            ['199001012020011118', 'Pensiun', 'PNS'],
            ['199001012020011119', 'Aktif', 'PPPK'],
        ] as [$nip, $status, $statusPegawai]) {
            $employee = $this->createNotificationEmployee($nip, "UNIT-{$status}-{$statusPegawai}-{$nip}");
            $employee->forceFill([
                'status' => $status,
                'status_pegawai' => $statusPegawai,
                'tingkat_pendidikan' => $statusPegawai === 'PNS' ? 'SD-AKTIF' : null,
                'id_pangkat' => $statusPegawai === 'PNS' ? $rank->id : null,
                'tmt_pangkat_baru' => '2027-01-04',
                'tmt_kgb_baru' => '2027-01-04',
                'tmt_pensiun' => '2027-05-04',
            ])->save();
            $employees[$nip] = $employee;
        }

        $service = app(NotificationService::class);
        $service->generateNotifications();
        $service->generateNotifications();

        $this->assertDatabaseCount('notifications', 5);
        foreach (['Pangkat', 'KGB', 'Pensiun'] as $jenis) {
            $this->assertDatabaseHas('notifications', [
                'nip' => $employees['199001012020011116']->nip,
                'jenis' => $jenis,
            ]);
        }
        foreach (['KGB', 'Pensiun'] as $jenis) {
            $this->assertDatabaseHas('notifications', [
                'nip' => $employees['199001012020011119']->nip,
                'jenis' => $jenis,
            ]);
        }
        $this->assertDatabaseMissing('notifications', [
            'nip' => $employees['199001012020011119']->nip,
            'jenis' => 'Pangkat',
        ]);
        foreach (['199001012020011117', '199001012020011118'] as $nip) {
            $this->assertDatabaseMissing('notifications', ['nip' => $nip]);
        }

        $oldNotificationIds = Notification::where(
            'nip',
            $employees['199001012020011116']->nip
        )->pluck('id');
        $employees['199001012020011116']->update(['status' => 'Nonaktif']);
        $service->generateNotifications();
        $this->assertDatabaseCount('notifications', 5);
        foreach ($oldNotificationIds as $notificationId) {
            $this->assertDatabaseHas('notifications', ['id' => $notificationId]);
        }

        $inactive = $employees['199001012020011117']->fresh();
        $service->generatePangkat($inactive);
        $service->generateKGB($inactive);
        $service->generatePensiun($inactive);
        $this->assertDatabaseCount('notifications', 5);
    }

    public function test_generate_command_runs_without_external_delivery(): void
    {
        Queue::fake();
        $this->createNotificationEmployee('199001012020011113', 'NOTIF-COMMAND');

        $this->artisan('notification:generate')
            ->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 0);
        Queue::assertNothingPushed();
    }

    public function test_notification_dispatch_queues_email_and_whatsapp_without_sending_externally(): void
    {
        Queue::fake();
        config(['cache.default' => 'database']);
        Cache::flush();
        $employee = $this->createNotificationEmployee('199001012020011112', 'NOTIF-QUEUE');
        $employee->forceFill([
            'email' => 'employee@example.test',
            'no_hp' => '081234567890',
        ])->save();
        User::factory()->create([
            'role' => 'user',
            'nip' => $employee->nip,
            'email' => 'account@example.test',
            'no_hp' => '081234567892',
        ]);
        User::factory()->create([
            'role' => 'super_admin',
            'email' => 'superadmin@example.test',
            'no_hp' => '081234567891',
        ]);

        app(NotificationService::class)->createNotification(
            $employee,
            'KGB',
            'Pengingat KGB',
            'Pesan antrean',
            Carbon::parse('2027-01-05')
        );
        app(NotificationService::class)->createNotification(
            $employee,
            'KGB',
            'Pengingat KGB',
            'Pesan antrean',
            Carbon::parse('2027-01-05')
        );

        Queue::assertPushed(SendEmailJob::class, 2);
        Queue::assertPushed(
            SendEmailJob::class,
            fn (SendEmailJob $job): bool => $job->email === 'account@example.test'
                && !$job->isSuperAdmin
        );
        Queue::assertPushed(
            SendEmailJob::class,
            fn (SendEmailJob $job): bool => $job->email === 'superadmin@example.test'
                && $job->isSuperAdmin
        );
        Queue::assertPushed(SendWhatsappJob::class, 2);
        Queue::assertPushed(
            SendWhatsappJob::class,
            fn (SendWhatsappJob $job): bool => $job->no_hp === '081234567892'
                && !$job->isSuperAdmin
        );
        Queue::assertPushed(
            SendWhatsappJob::class,
            fn (SendWhatsappJob $job): bool => $job->no_hp === '081234567891'
                && $job->isSuperAdmin
        );
    }

    public function test_unique_job_keys_allow_channels_recipients_and_notifications_to_coexist(): void
    {
        Queue::fake();
        config(['cache.default' => 'database']);
        Cache::flush();
        $employee = $this->createNotificationEmployee('199001012020011114', 'NOTIF-UNIQUE');
        $notification = $this->addNotification($employee, 'KGB');
        $otherNotification = $this->addNotification($employee, 'Pangkat');
        $emailJob = new SendEmailJob($notification, 'employee@example.test');
        $whatsappJob = new SendWhatsappJob($notification, '081234567890');
        $superAdminEmailJob = new SendEmailJob(
            $notification,
            'admin@example.test',
            true
        );
        $otherEmailJob = new SendEmailJob($otherNotification, 'employee@example.test');

        $this->assertSame(
            "{$notification->id}:email:employee",
            $emailJob->uniqueId()
        );
        $this->assertSame(
            "{$notification->id}:whatsapp:employee",
            $whatsappJob->uniqueId()
        );
        $this->assertNotSame($emailJob->uniqueId(), $superAdminEmailJob->uniqueId());
        $this->assertNotSame($emailJob->uniqueId(), $otherEmailJob->uniqueId());
        $this->assertGreaterThan(
            ($emailJob->timeout * $emailJob->tries) + array_sum($emailJob->backoff()),
            $emailJob->uniqueFor()
        );
        $this->assertGreaterThan(
            ($whatsappJob->timeout * $whatsappJob->tries) + array_sum($whatsappJob->backoff()),
            $whatsappJob->uniqueFor()
        );

        $emailJob->delay(now()->addHours(3));
        $this->assertGreaterThan(
            10800 + ($emailJob->timeout * $emailJob->tries) + array_sum($emailJob->backoff()),
            $emailJob->uniqueFor()
        );

        SendEmailJob::dispatch($notification, 'employee@example.test');
        SendEmailJob::dispatch($notification, 'employee@example.test');
        SendEmailJob::dispatch($otherNotification, 'employee@example.test');
        Queue::assertPushed(SendEmailJob::class, 2);
    }

    public function test_unique_locks_are_released_after_job_success_and_permanent_failure(): void
    {
        config([
            'queue.default' => 'sync',
            'cache.default' => 'database',
        ]);
        Cache::flush();
        Mail::fake();
        $notification = $this->addNotification(
            $this->createNotificationEmployee('199001012020011115', 'NOTIF-LOCK'),
            'KGB'
        );
        $job = new SendEmailJob($notification, 'employee@example.test');

        try {
            $job::dispatch($notification, 'employee@example.test');
        } finally {
            $lock = Cache::lock(UniqueLock::getKey($job), 0);
            $this->assertTrue($lock->get());
            $lock->release();
        }

        $failedJob = new SendWhatsappJob($notification, '081234567890');
        $logger = Log::spy();
        $whatsapp = \Mockery::mock(\App\Services\WhatsAppService::class);
        $whatsapp->shouldReceive('sendOrFail')
            ->once()
            ->andThrow(new \App\Exceptions\WhatsAppDeliveryException(
                'provider_rejected',
                'Fonnte rejected the WhatsApp delivery.',
                503
            ));
        app()->instance(\App\Services\WhatsAppService::class, $whatsapp);

        try {
            $failedJob::dispatch($notification, '081234567890');
            $this->fail('The synchronous queue should propagate the delivery failure.');
        } catch (\App\Exceptions\WhatsAppDeliveryException) {
            $lock = Cache::lock(UniqueLock::getKey($failedJob), 0);
            $this->assertTrue($lock->get());
            $lock->release();
        }
        $logger->shouldHaveReceived('error')
            ->once()
            ->withArgs(
                fn (string $message, array $context): bool =>
                    $context['notification_id'] === $notification->id
                    && $context['failure_type'] === 'provider_rejected'
            );

        $retry = \Mockery::mock(\App\Services\WhatsAppService::class);
        $retry->shouldReceive('sendOrFail')->once()->andReturnNull();
        app()->instance(\App\Services\WhatsAppService::class, $retry);
        SendWhatsappJob::dispatch($notification, '081234567890');
        $this->assertTrue($notification->fresh()->whatsapp_terkirim);
    }

    public function test_processed_kgb_notification_is_created_once_with_actual_pay_information(): void
    {
        Queue::fake();
        $employee = $this->createNotificationEmployee('199001012020011110', 'NOTIF-I');
        $employee->forceFill([
            'tmt_kgb_lama' => '2024-10-01',
            'tmt_kgb_baru' => '2026-10-01',
        ])->save();
        $service = app(NotificationService::class);

        $service->notifyKgbProcessed($employee, 3100000);
        $service->notifyKgbProcessed($employee, 3100000);

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'nip' => $employee->nip,
            'jenis' => 'KGB Diproses',
        ]);
        $this->assertSame(
            '2026-10-01',
            Notification::firstOrFail()->tanggal_referensi->toDateString()
        );
        $this->assertStringContainsString(
            'Rp 3.100.000',
            Notification::firstOrFail()->pesan
        );
    }
}

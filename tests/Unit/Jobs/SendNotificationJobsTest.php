<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendEmailJob;
use App\Jobs\ResendNotificationJob;
use App\Jobs\SendWhatsappJob;
use App\Exceptions\WhatsAppDeliveryException;
use App\Mail\NotificationMail;
use App\Models\Employee;
use App\Models\Notification;
use App\Services\WhatsAppService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Tests\TestCase;

class SendNotificationJobsTest extends TestCase
{
    use RefreshDatabase;

    private function createNotification(): Notification
    {
        $employee = Employee::create([
            'nip' => '199001012020011001',
            'nama' => 'Pegawai Notifikasi',
            'status_pegawai' => 'PNS',
        ]);

        return Notification::create([
            'nip' => $employee->nip,
            'jenis' => 'KGB',
            'judul' => 'Pengingat KGB',
            'pesan' => 'Pengingat kenaikan gaji berkala.',
            'tanggal_notifikasi' => today()->toDateString(),
            'tanggal_referensi' => today()->addMonth()->toDateString(),
        ]);
    }

    public function test_email_job_sends_and_marks_regular_recipient(): void
    {
        Mail::fake();
        $notification = $this->createNotification();

        (new SendEmailJob($notification, 'pegawai@example.test'))->handle();

        Mail::assertSent(
            NotificationMail::class,
            fn (NotificationMail $mail): bool => $mail->envelope()->subject === $notification->judul
        );
        $this->assertTrue($notification->fresh()->email_terkirim);
        $this->assertFalse($notification->fresh()->email_super_admin_terkirim);
    }

    public function test_email_job_sends_and_marks_super_admin_recipient(): void
    {
        Mail::fake();
        $notification = $this->createNotification();

        (new SendEmailJob($notification, 'admin@example.test', true))->handle();

        Mail::assertSent(NotificationMail::class);
        $this->assertTrue($notification->fresh()->email_super_admin_terkirim);
        $this->assertFalse($notification->fresh()->email_terkirim);
    }

    public function test_email_job_skips_an_already_sent_regular_recipient(): void
    {
        Mail::fake();
        $notification = $this->createNotification();
        $notification->update(['email_terkirim' => true]);

        (new SendEmailJob($notification, 'pegawai@example.test'))->handle();

        Mail::assertNothingSent();
        $this->assertTrue($notification->fresh()->email_terkirim);
    }

    public function test_email_job_skips_an_already_sent_super_admin_recipient(): void
    {
        Mail::fake();
        $notification = $this->createNotification();
        $notification->update(['email_super_admin_terkirim' => true]);

        (new SendEmailJob($notification, 'admin@example.test', true))->handle();

        Mail::assertNothingSent();
        $this->assertTrue($notification->fresh()->email_super_admin_terkirim);
    }

    public function test_email_job_skips_empty_email_address(): void
    {
        Mail::fake();
        $notification = $this->createNotification();

        (new SendEmailJob($notification, ''))->handle();

        Mail::assertNothingSent();
        $this->assertFalse($notification->fresh()->email_terkirim);
    }

    public function test_email_job_skips_notification_that_has_been_deleted(): void
    {
        Mail::fake();
        $notification = $this->createNotification();
        $notification->forceDelete();

        (new SendEmailJob($notification, 'pegawai@example.test'))->handle();

        Mail::assertNothingSent();
    }

    public function test_email_job_rethrows_mail_failure(): void
    {
        $notification = $this->createNotification();
        Mail::shouldReceive('to')
            ->once()
            ->with('pegawai@example.test')
            ->andThrow(new RuntimeException('Mail transport failed.'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mail transport failed.');

        (new SendEmailJob($notification, 'pegawai@example.test'))->handle();
    }

    public function test_whatsapp_job_sends_and_marks_regular_recipient(): void
    {
        $notification = $this->createNotification();
        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldReceive('sendOrFail')
            ->once()
            ->with('6281234567890', Mockery::type('string'))
            ->andReturnNull();

        (new SendWhatsappJob($notification, '081234567890'))->handle($whatsapp);

        $this->assertTrue($notification->fresh()->whatsapp_terkirim);
        $this->assertFalse($notification->fresh()->whatsapp_super_admin_terkirim);
    }

    public function test_whatsapp_job_sends_and_marks_super_admin_recipient(): void
    {
        $notification = $this->createNotification();
        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldReceive('sendOrFail')
            ->once()
            ->with('6281234567890', Mockery::type('string'))
            ->andReturnNull();

        (new SendWhatsappJob($notification, '081234567890', true))
            ->handle($whatsapp);

        $this->assertTrue($notification->fresh()->whatsapp_super_admin_terkirim);
        $this->assertFalse($notification->fresh()->whatsapp_terkirim);
    }

    public function test_whatsapp_job_skips_empty_phone_number(): void
    {
        $notification = $this->createNotification();
        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldNotReceive('sendOrFail');

        (new SendWhatsappJob($notification, ''))->handle($whatsapp);

        $this->assertFalse($notification->fresh()->whatsapp_terkirim);
    }

    public function test_whatsapp_job_skips_notification_that_has_been_deleted(): void
    {
        $notification = $this->createNotification();
        $notification->forceDelete();
        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldNotReceive('sendOrFail');

        (new SendWhatsappJob($notification, '081234567890'))->handle($whatsapp);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_whatsapp_job_skips_invalid_phone_number(): void
    {
        $notification = $this->createNotification();
        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldNotReceive('sendOrFail');

        (new SendWhatsappJob($notification, 'invalid'))->handle($whatsapp);

        $this->assertFalse($notification->fresh()->whatsapp_terkirim);
    }

    public function test_whatsapp_job_skips_an_already_sent_regular_recipient(): void
    {
        $notification = $this->createNotification();
        $notification->update(['whatsapp_terkirim' => true]);
        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldNotReceive('sendOrFail');

        (new SendWhatsappJob($notification, '081234567890'))->handle($whatsapp);

        $this->assertTrue($notification->fresh()->whatsapp_terkirim);
    }

    public function test_whatsapp_job_skips_an_already_sent_super_admin_recipient(): void
    {
        $notification = $this->createNotification();
        $notification->update(['whatsapp_super_admin_terkirim' => true]);
        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldNotReceive('sendOrFail');

        (new SendWhatsappJob($notification, '081234567890', true))
            ->handle($whatsapp);

        $this->assertTrue($notification->fresh()->whatsapp_super_admin_terkirim);
    }

    public function test_whatsapp_job_rethrows_send_failure(): void
    {
        $notification = $this->createNotification();
        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldReceive('sendOrFail')
            ->once()
            ->with('6281234567890', Mockery::type('string'))
            ->andThrow(new WhatsAppDeliveryException(
                'provider_rejected',
                'Fonnte rejected the WhatsApp delivery.',
                503
            ));

        $this->expectException(WhatsAppDeliveryException::class);
        $this->expectExceptionMessage('Fonnte rejected the WhatsApp delivery.');

        (new SendWhatsappJob($notification, '081234567890'))->handle($whatsapp);
    }

    public function test_permanent_failure_logs_safe_context_without_recipient_or_message_data(): void
    {
        $logger = Log::spy();
        $notification = $this->createNotification();
        $job = new SendWhatsappJob($notification, '6281234567890');
        $queueJob = Mockery::mock(QueueJob::class);
        $queueJob->shouldReceive('getJobId')->once()->andReturn('queue-job-42');
        $queueJob->shouldReceive('attempts')->once()->andReturn(10);
        $job->job = $queueJob;
        $job->failed(new WhatsAppDeliveryException(
            'provider_rejected',
            'Fonnte rejected the WhatsApp delivery.',
            503
        ));

        $logger->shouldHaveReceived('error')
            ->once()
            ->withArgs(
                fn (string $message, array $context): bool =>
                $context['notification_id'] === $notification->id
                && $context['channel'] === 'whatsapp'
                && $context['job_id'] === 'queue-job-42'
                && $context['attempts'] === 10
                && $context['failure_type'] === 'provider_rejected'
                && $context['http_status'] === 503
                && $context['error'] === 'Fonnte rejected the WhatsApp delivery.'
                && isset($context['failed_at'])
                && !array_key_exists('no_hp', $context)
                && !array_key_exists('pesan', $context)
                && !array_key_exists('token', $context)
                && !array_key_exists('message', $context)
                && !str_contains($message, '6281234567890')
            );
        $this->assertFalse($notification->fresh()->whatsapp_terkirim);
    }

    public function test_whatsapp_failure_keeps_its_flag_false_and_does_not_block_email(): void
    {
        Mail::fake();
        $notification = $this->createNotification();
        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldReceive('sendOrFail')
            ->once()
            ->andThrow(new WhatsAppDeliveryException(
                'connection_error',
                'Could not connect to Fonnte.'
            ));

        try {
            (new SendWhatsappJob($notification, '081234567890'))
                ->handle($whatsapp);
            $this->fail('WhatsApp failure should be retried by the queue.');
        } catch (WhatsAppDeliveryException) {
            $this->assertFalse($notification->fresh()->whatsapp_terkirim);
        }

        (new SendEmailJob($notification, 'pegawai@example.test'))->handle();

        Mail::assertSent(NotificationMail::class);
        $this->assertTrue($notification->fresh()->email_terkirim);
        $this->assertFalse($notification->fresh()->whatsapp_terkirim);
    }

    public function test_delivery_jobs_have_independent_unique_channel_keys_and_retry_limits(): void
    {
        $notification = $this->createNotification();
        $emailJob = new SendEmailJob($notification, 'pegawai@example.test');
        $whatsappJob = new SendWhatsappJob($notification, '081234567890');

        $this->assertSame("{$notification->id}:email:employee", $emailJob->uniqueId());
        $this->assertSame("{$notification->id}:whatsapp:employee", $whatsappJob->uniqueId());
        $this->assertSame(3, $emailJob->tries);
        $this->assertSame(10, $whatsappJob->tries);
        $this->assertSame([60, 180, 600], $emailJob->backoff());
        $this->assertSame([10, 20, 30, 60, 120, 300, 600, 900, 1800], $whatsappJob->backoff());
    }

    public function test_resend_job_skips_missing_employee_and_resends_for_existing_employee(): void
    {
        $service = Mockery::mock(NotificationService::class);

        (new ResendNotificationJob('missing-nip'))->handle($service);

        $employee = $this->createNotification()->employee;
        $service->shouldReceive('resendPendingNotifications')
            ->once()
            ->with(Mockery::on(
                fn (Employee $found): bool =>
                    $found->is($employee)
                    && $found->relationLoaded('notifications')
                    && $found->notifications->contains('id', $employee->notifications->sole()->id)
            ));

        (new ResendNotificationJob($employee->nip))->handle($service);
    }

    public function test_unique_lock_lifetime_includes_supported_delivery_delays(): void
    {
        Carbon::setTestNow('2026-11-14 10:00:00');

        try {
            $notification = $this->createNotification();
            $emailBase = 3600 + (120 * 3) + array_sum([60, 180, 600]);
            $whatsappBase = 3600 + (120 * 10) + array_sum([10, 20, 30, 60, 120, 300, 600, 900, 1800]);

            $emailJob = new SendEmailJob($notification, 'pegawai@example.test');
            $whatsappJob = new SendWhatsappJob($notification, '081234567890');

            $this->assertSame($emailBase + 45, $emailJob->delay(45)->uniqueFor());
            $this->assertSame(
                $emailBase + 300,
                $emailJob->delay(new \DateInterval('PT5M'))->uniqueFor()
            );
            $this->assertSame(
                $emailBase + 30,
                $emailJob->delay(now()->addSeconds(30))->uniqueFor()
            );
            $this->assertSame($emailBase, $emailJob->delay(null)->uniqueFor());

            $this->assertSame($whatsappBase + 45, $whatsappJob->delay(45)->uniqueFor());
            $this->assertSame(
                $whatsappBase + 300,
                $whatsappJob->delay(new \DateInterval('PT5M'))->uniqueFor()
            );
            $this->assertSame(
                $whatsappBase + 30,
                $whatsappJob->delay(now()->addSeconds(30))->uniqueFor()
            );
            $this->assertSame($whatsappBase, $whatsappJob->delay(null)->uniqueFor());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_whatsapp_permanent_failure_logs_generic_exception_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(
                fn (string $message, array $context): bool =>
                    $context['failure_type'] === 'exception'
                    && $context['http_status'] === null
                    && $context['error'] === 'WhatsApp notification delivery failed.'
                    && $context['job_id'] === 'queue-job-43'
                    && $context['attempts'] === 3
            );
        $notification = $this->createNotification();
        $queueJob = Mockery::mock(QueueJob::class);
        $queueJob->shouldReceive('getJobId')->once()->andReturn('queue-job-43');
        $queueJob->shouldReceive('attempts')->once()->andReturn(3);
        $job = new SendWhatsappJob($notification, '081234567890');
        $job->job = $queueJob;

        $job->failed(new RuntimeException('Unexpected delivery error.'));
    }
}

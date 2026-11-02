<?php
namespace App\Jobs;
use App\Mail\NotificationMail;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    // Maksimal dicoba 3 kali
    public $tries = 3;
    public $timeout = 120;
    /**
     * Jeda retry
     */
    public function backoff(): array
    {
        return [
            60,
            180,
            600,
        ];
    }
    public function __construct(
        public Notification $notification,
        public string $email,
        public bool $isSuperAdmin = false,
    ) {}
    public function handle(): void
    {
        $notification = $this->notification->fresh();
        if (!$notification) {
            return;
        }
        if (empty($this->email)) {
            return;
        }
        if (!$this->isSuperAdmin && $notification->email_terkirim) {
            return;
        }
        if ($this->isSuperAdmin && $notification->email_super_admin_terkirim) {
            return;
        }
        try {
            Mail::to($this->email)
                ->send(new NotificationMail($notification));
            if ($this->isSuperAdmin) {
                $notification->update([
                    'email_super_admin_terkirim' => true,
                ]);
            } else {
                $notification->update([
                    'email_terkirim' => true,
                ]);
            }
            // Log::info('Email berhasil dikirim', [
            //     'notification_id' => $notification->id,
            //     'super_admin' => $this->isSuperAdmin,
            // ]);
        } catch (\Throwable $e) {
            // Log::error('Email gagal dikirim', [
            //     'notification_id' => $notification->id,
            //     'super_admin' => $this->isSuperAdmin,
            //     'error' => $e->getMessage(),
            // ]);
            throw $e;
        }
    }
}

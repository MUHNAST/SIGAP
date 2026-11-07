<?php
namespace App\Jobs;
use App\Mail\NotificationMail;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use DateInterval;
use DateTimeInterface;
class SendEmailJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private const UNIQUE_LOCK_BUFFER_SECONDS = 3600;
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

    public function uniqueId(): string
    {
        return implode(':', [
            $this->notification->getKey(),
            'email',
            $this->isSuperAdmin ? 'super-admin' : 'employee',
        ]);
    }

    public function uniqueFor(): int
    {
        return $this->delaySeconds()
            + ($this->timeout * $this->tries)
            + array_sum($this->backoff())
            + self::UNIQUE_LOCK_BUFFER_SECONDS;
    }

    private function delaySeconds(): int
    {
        if ($this->delay instanceof DateTimeInterface) {
            return max(0, now()->diffInSeconds($this->delay, false));
        }

        if ($this->delay instanceof DateInterval) {
            return max(0, now()->diffInSeconds(now()->add($this->delay), false));
        }

        return is_numeric($this->delay)
            ? max(0, (int) $this->delay)
            : 0;
    }

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

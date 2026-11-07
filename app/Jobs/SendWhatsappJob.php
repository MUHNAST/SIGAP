<?php
namespace App\Jobs;
use App\Exceptions\WhatsAppDeliveryException;
use App\Models\Notification;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use DateInterval;
use DateTimeInterface;
class SendWhatsappJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private const UNIQUE_LOCK_BUFFER_SECONDS = 3600;
    // Maksimal dicoba 10 kali
    public $tries = 10;
    public $timeout = 120;
    public function __construct(
        public Notification $notification,
        public string $no_hp,
        public bool $isSuperAdmin = false
    ) {}

    public function uniqueId(): string
    {
        return implode(':', [
            $this->notification->getKey(),
            'whatsapp',
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

    /**
     * Jeda retry.
     */
    public function backoff(): array
    {
        return [
            10,
            20,
            30,
            60,
            120,
            300,
            600,
            900,
            1800,
        ];
    }
    public function handle(WhatsAppService $whatsapp): void
    {
        $notification = $this->notification->fresh();
        if (!$notification) {
            return;
        }
        // Log::info('SendWhatsappJob mulai', [
        //     'notification' => $notification->id,
        //     'target_phone' => '****' . substr($this->no_hp, -4),
        //     'super_admin' => $this->isSuperAdmin,
        // ]);
        if (empty($this->no_hp)) {
            // Log::warning('Nomor WhatsApp kosong', [
            //     'notification' => $notification->id,
            // ]);
            return;
        }
        if (!$this->isSuperAdmin && $notification->whatsapp_terkirim) {
            return;
        }
        if ($this->isSuperAdmin && $notification->whatsapp_super_admin_terkirim) {
            return;
        }
        $no_hp = $this->normalizePhone($this->no_hp);
        if (!$no_hp) {
            // Log::warning('Nomor WhatsApp tidak valid.', [
            //     'notification' => $notification->id,
            //     'no_hp' => '****' . substr($this->no_hp, -4),
            // ]);
            return;
        }
        try {
            $message = $this->formatWhatsappMessage($notification);
            // Log::info('Pesan WA yang dikirim', [
            //     'notification_id' => $notification->id,
            //     // Menggunakan nama atribut sesuai migration
            //     'judul' => $notification->judul,
            //     'pesan' => $notification->pesan,
            //     'formatted_message' => $message,
            //     'no_hp' => '****' . substr($no_hp, -4),
            //     'super_admin' => $this->isSuperAdmin,
            // ]);
            $whatsapp->sendOrFail($no_hp, $message);
            /*
            |--------------------------------------------------------------------------
            | Update status berdasarkan penerima
            |--------------------------------------------------------------------------
            */
            if ($this->isSuperAdmin) {
                $notification->update(['whatsapp_super_admin_terkirim' => true,]);
            } else {
                $notification->update(['whatsapp_terkirim' => true,]);
            }
            // Log::info('WhatsApp berhasil dikirim', [
            //     'notification' => $notification->id,
            //     'no_hp' => '****' . substr($no_hp, -4),
            //     'super_admin' => $this->isSuperAdmin,
            // ]);
        } catch (\Throwable $e) {
            // Log::error('WhatsApp gagal dikirim', [
            //     'notification' => $notification->id,
            //     'no_hp' => '****' . substr($no_hp, -4),
            //     'super_admin' => $this->isSuperAdmin,
            //     'error' => $e->getMessage(),
            // ]);
            throw $e;
        }
    }
    public function failed(\Throwable $e): void
    {
        $deliveryFailure = $e instanceof WhatsAppDeliveryException
            ? $e
            : null;

        Log::error('WhatsApp notification delivery permanently failed.', [
            'notification_id' => $this->notification->getKey(),
            'channel' => 'whatsapp',
            'job_id' => $this->job?->getJobId(),
            'attempts' => $this->attempts(),
            'failure_type' => $deliveryFailure?->failureType ?? 'exception',
            'http_status' => $deliveryFailure?->httpStatus,
            'error' => $deliveryFailure?->getMessage()
                ?? 'WhatsApp notification delivery failed.',
            'failed_at' => now('Asia/Jakarta')->toIso8601String(),
        ]);
    }
    /**
     * Format pesan WhatsApp.
     */
    private function formatWhatsappMessage(Notification $notification): string
    {
        return
            "🏛️ *SIGAP*\n" .
            "*Sistem Informasi Kepegawaian*\n" .
            "Kecamatan Pekalongan Timur\n" .
            "━━━━━━━━━━━━━━━━━━━━━━\n" .
            "📌 *{$notification->judul}*\n" .
            "━━━━━━━━━━━━━━━━━━━━━━\n" .
            "{$notification->pesan}\n" .
            "━━━━━━━━━━━━━━━━━━━━━━\n" .
            "🕒 *Tanggal Notifikasi*\n" .
            now('Asia/Jakarta')->translatedFormat('d F Y H:i') . "\n" .
            "🤖 Pesan ini dikirim otomatis oleh aplikasi SIGAP.\n" .
            "Apabila memerlukan bantuan atau informasi lebih lanjut, silakan menghubungi Admin Kecamatan Pekalongan Timur.\n" .
            "_Mohon tidak membalas pesan ini._";
    }
    /**
     * Normalisasi nomor WhatsApp.
     */
    private function normalizePhone(?string $no_hp): ?string
    {
        if (!$no_hp) {
            return null;
        }
        $no_hp = preg_replace('/\D+/', '', $no_hp);
        if (str_starts_with($no_hp, '0')) {
            $no_hp = '62' . substr($no_hp, 1);
        }
        if (!str_starts_with($no_hp, '62')) {
            return null;
        }
        if (strlen($no_hp) < 11 || strlen($no_hp) > 15) {
            return null;
        }
        return $no_hp;
    }
}

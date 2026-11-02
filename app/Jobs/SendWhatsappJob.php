<?php
namespace App\Jobs;
use App\Models\Notification;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
class SendWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    // Maksimal dicoba 10 kali
    public $tries = 10;
    public $timeout = 120;
    public function __construct(
        public Notification $notification,
        public string $no_hp,
        public bool $isSuperAdmin = false
    ) {}
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
            $success = $whatsapp->send($no_hp, $message);
            if (!$success) {
                throw new \Exception('Gagal mengirim WhatsApp.');
            }
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
        // Log::critical('SendWhatsappJob gagal permanen', [
        //     'notification' => $this->notification->id,
        //     'super_admin' => $this->isSuperAdmin,
        //     'error' => $e->getMessage(),
        // ]);
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
            now()->translatedFormat('d F Y H:i') . "\n" .
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

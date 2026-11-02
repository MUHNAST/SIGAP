<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class WhatsAppService
{
    public function send(string $no_hp, string $message): bool
    {
        $no_hp = $this->normalizePhone($no_hp);
        $tokens = array_filter([
            config('services.fonnte.token_1'),
            config('services.fonnte.token_2'),
        ]);
        if (empty($tokens)) {
            // Log::warning('Token Fonnte belum dikonfigurasi.');
            return false;
        }
        foreach ($tokens as $index => $token) {
            try {
                $response = Http::withoutVerifying()
                    ->retry(3, 5000)
                    ->timeout(30)
                    ->acceptJson()
                    ->withHeaders([
                        'Authorization' => $token,
                    ])
                    ->post('https://api.fonnte.com/send', [
                        'target'  => $no_hp,
                        'message' => $message,
                    ]);
                if (!$response->successful()) {
                    // Log::warning('HTTP Error Fonnte.', [
                    //     'token'  => $index + 1,
                    //     'status' => $response->status(),
                    //     'body'   => $response->body(),
                    // ]);
                    continue;
                }
                $result = $response->json();
                if (($result['status'] ?? false) !== true) {
                    // Log::warning('Fonnte menolak pengiriman.', [
                    //     'token'    => $index + 1,
                    //     'response' => $result,
                    // ]);
                    continue;
                }
                // Log::info('WhatsApp berhasil dikirim.', [
                //     'token'     => $index + 1,
                //     'phone'     => $no_hp,
                //     'requestid' => $result['requestid'] ?? null,
                //     'detail'    => $result['detail'] ?? null,
                // ]);
                return true;
            } catch (\Throwable $e) {
                // Log::error('Exception WhatsApp.', [
                //     'token'   => $index + 1,
                //     'message' => $e->getMessage(),
                // ]);
            }
        }
        // Log::error('Semua token Fonnte gagal mengirim pesan.', [
        //     'phone' => $no_hp,
        // ]);
        return false;
    }
    /**
     * Ubah nomor menjadi format internasional.
     */
    private function normalizePhone(string $no_hp): string
    {
        // Hapus semua karakter selain angka
        $no_hp = preg_replace('/\D/', '', trim($no_hp));

        // 6208123... -> 628123...
        if (str_starts_with($no_hp, '620')) {
            return '62' . substr($no_hp, 3);
        }

        // 08123... -> 628123...
        if (str_starts_with($no_hp, '0')) {
            return '62' . substr($no_hp, 1);
        }

        // 628123... -> tetap
        if (str_starts_with($no_hp, '62')) {
            return $no_hp;
        }

        // Jika tidak diawali 0 atau 62, anggap nomor lokal
        return '62' . ltrim($no_hp, '0');
    }
}

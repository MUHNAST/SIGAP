<?php
namespace App\Services;

use App\Exceptions\WhatsAppDeliveryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
class WhatsAppService
{
    public function send(string $no_hp, string $message): bool
    {
        try {
            $this->sendOrFail($no_hp, $message);
            return true;
        } catch (WhatsAppDeliveryException) {
            return false;
        }
    }

    public function sendOrFail(string $no_hp, string $message): void
    {
        $no_hp = $this->normalizePhone($no_hp);
        $tokens = array_filter([
            config('services.fonnte.token_1'),
            config('services.fonnte.token_2'),
        ]);
        if (empty($tokens)) {
            throw new WhatsAppDeliveryException(
                'configuration_missing',
                'Fonnte token is not configured.'
            );
        }

        $lastFailure = null;
        foreach ($tokens as $token) {
            try {
                $response = Http::retry(3, 5000)
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
                    $lastFailure = new WhatsAppDeliveryException(
                        'http_error',
                        'Fonnte returned an unsuccessful HTTP response.',
                        $response->status()
                    );
                    continue;
                }
                $result = $response->json();
                if (($result['status'] ?? false) !== true) {
                    $lastFailure = new WhatsAppDeliveryException(
                        'provider_rejected',
                        'Fonnte rejected the WhatsApp delivery.',
                        $response->status()
                    );
                    continue;
                }
                return;
            } catch (RequestException $e) {
                $lastFailure = new WhatsAppDeliveryException(
                    'http_error',
                    'Fonnte returned an unsuccessful HTTP response.',
                    $e->response->status()
                );
            } catch (ConnectionException $e) {
                $isTimeout = str_contains(
                    strtolower($e->getMessage()),
                    'timed out'
                );
                $lastFailure = new WhatsAppDeliveryException(
                    $isTimeout ? 'timeout' : 'connection_error',
                    $isTimeout
                        ? 'Fonnte request timed out.'
                        : 'Could not connect to Fonnte.'
                );
            } catch (\Throwable) {
                $lastFailure = new WhatsAppDeliveryException(
                    'exception',
                    'An unexpected error occurred during WhatsApp delivery.'
                );
            }
        }

        throw $lastFailure ?? new WhatsAppDeliveryException(
            'provider_rejected',
            'Fonnte rejected the WhatsApp delivery.'
        );
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

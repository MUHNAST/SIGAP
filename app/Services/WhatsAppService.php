<?php
namespace App\Services;
use App\Exceptions\WhatsAppDeliveryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
class WhatsAppService
{
    private const API_URL = 'https://api.fonnte.com/send';
    public function send(string $no_hp, string $message): bool
    {
        try {
            $this->sendOrFail($no_hp, $message);
            // Log::info('WhatsApp send operation completed.', [
            //     'channel' => 'whatsapp',
            //     'result' => 'success',
            // ]);
            return true;
        } catch (WhatsAppDeliveryException $e) {
            // Log::warning('WhatsApp send operation failed.', [
            //     'channel' => 'whatsapp',
            //     'failure_type' => $e->getCode() ?: 'delivery_error',
            //     'error' => $e->getMessage(),
            // ]);
            return false;
        } catch (Throwable $e) {
            // Log::error('Unexpected error in WhatsApp send method.', [
            //     'exception' => get_class($e),
            //     'message' => $e->getMessage(),
            // ]);
            return false;
        }
    }
    public function sendOrFail(string $no_hp, string $message): void
    {
        // Log::info('Starting WhatsApp delivery process.', [
        //     'channel' => 'whatsapp',
        // ]);
        /*
        |--------------------------------------------------------------------------
        | 1. Validasi dan normalisasi nomor
        |--------------------------------------------------------------------------
        */
        $originalPhone = $no_hp;
        $no_hp = $this->normalizePhone($no_hp);
        if (!$no_hp) {
            // Log::warning('WhatsApp delivery skipped: invalid phone number.', [
            //     'channel' => 'whatsapp',
            //     'phone_suffix' => substr(
            //         preg_replace('/\D+/', '', $originalPhone),
            //         -4
            //     ),
            // ]);
            throw new WhatsAppDeliveryException(
                'invalid_target',
                'WhatsApp destination number is invalid.'
            );
        }
        // Log::info('WhatsApp destination validated.', [
        //     'channel' => 'whatsapp',
        //     'phone_suffix' => substr($no_hp, -4),
        // ]);
        /*
        |--------------------------------------------------------------------------
        | 2. Ambil konfigurasi token
        |--------------------------------------------------------------------------
        */
        $tokens = array_values(array_filter([
            config('services.fonnte.token_1'),
            config('services.fonnte.token_2'),
        ], fn ($token) => is_string($token) && trim($token) !== ''));
        if (empty($tokens)) {
            // Log::critical('WhatsApp delivery failed: Fonnte tokens are missing.', [
            //     'channel' => 'whatsapp',
            //     'configured_tokens' => 0,
            // ]);
            throw new WhatsAppDeliveryException(
                'configuration_missing',
                'Fonnte token is not configured.'
            );
        }
        // Log::info('Fonnte configuration loaded.', [
        //     'channel' => 'whatsapp',
        //     'configured_tokens' => count($tokens),
        // ]);
        /*
        |--------------------------------------------------------------------------
        | 3. Proses pengiriman menggunakan token
        |--------------------------------------------------------------------------
        */
        $lastFailure = null;
        foreach ($tokens as $index => $token) {
            $tokenNumber = $index + 1;
            // Log::info('Attempting WhatsApp delivery through Fonnte.', [
            //     'channel' => 'whatsapp',
            //     'token_number' => $tokenNumber,
            //     'attempt' => $tokenNumber,
            //     'phone_suffix' => substr($no_hp, -4),
            // ]);
            try {
                $response = Http::retry(3, 5000)
                    ->timeout(30)
                    ->acceptJson()
                    ->withHeaders([
                        'Authorization' => $token,
                    ])
                    ->post(self::API_URL, [
                        'target' => $no_hp,
                        'message' => $message,
                    ]);
                /*
                |--------------------------------------------------------------------------
                | 4. Periksa HTTP response
                |--------------------------------------------------------------------------
                */
                $httpStatus = $response->status();
                if (!$response->successful()) {
                    // Log::error('Fonnte returned unsuccessful HTTP response.', [
                    //     'channel' => 'whatsapp',
                    //     'token_number' => $tokenNumber,
                    //     'http_status' => $httpStatus,
                    //     'response_body' => mb_substr(
                    //         $response->body(),
                    //         0,
                    //         2000
                    //     ),
                    // ]);
                    $lastFailure = new WhatsAppDeliveryException(
                        'http_error',
                        'Fonnte returned an unsuccessful HTTP response.',
                        $httpStatus
                    );
                    continue;
                }
                /*
                |--------------------------------------------------------------------------
                | 5. Periksa respons JSON Fonnte
                |--------------------------------------------------------------------------
                */
                $result = $response->json();
                if (!is_array($result)) {
                    // Log::error('Fonnte returned an invalid JSON response.', [
                    //     'channel' => 'whatsapp',
                    //     'token_number' => $tokenNumber,
                    //     'http_status' => $httpStatus,
                    //     'response_body' => mb_substr(
                    //         $response->body(),
                    //         0,
                    //         2000
                    //     ),
                    // ]);
                    $lastFailure = new WhatsAppDeliveryException(
                        'invalid_response',
                        'Fonnte returned an invalid response.',
                        $httpStatus
                    );
                    continue;
                }
                $status = $result['status'] ?? null;
                $reason = $result['reason'] ?? null;
                $detail = $result['detail'] ?? null;
                $requestId = $result['requestid'] ?? null;
                $isSuccessful = in_array(
                    $status,
                    [true, 1, '1', 'true'],
                    true
                );
                /*
                |--------------------------------------------------------------------------
                | 6. Fonnte menolak pengiriman
                |--------------------------------------------------------------------------
                */
                if (!$isSuccessful) {
                    // Log::warning('Fonnte rejected WhatsApp delivery.', [
                    //     'channel' => 'whatsapp',
                    //     'token_number' => $tokenNumber,
                    //     'http_status' => $httpStatus,
                    //     'provider_status' => $status,
                    //     'reason' => $reason,
                    //     'detail' => $detail,
                    //     'request_id' => $requestId,
                    //     'target_suffix' => substr($no_hp, -4),
                    // ]);
                    $errorMessage = 'Fonnte rejected WhatsApp delivery.';
                    if (is_string($reason) && $reason !== '') {
                        $errorMessage .= ' Reason: ' . $reason;
                    }
                    $lastFailure = new WhatsAppDeliveryException(
                        'provider_rejected',
                        $errorMessage,
                        $httpStatus
                    );
                    continue;
                }
                /*
                |--------------------------------------------------------------------------
                | 7. Fonnte menerima permintaan pengiriman
                |--------------------------------------------------------------------------
                */
                // Log::info('Fonnte accepted WhatsApp delivery request.', [
                //     'channel' => 'whatsapp',
                //     'token_number' => $tokenNumber,
                //     'http_status' => $httpStatus,
                //     'provider_status' => $status,
                //     'request_id' => $requestId,
                //     'target_suffix' => substr($no_hp, -4),
                // ]);
                return;
            } catch (ConnectionException $e) {
                $errorMessage = strtolower($e->getMessage());
                $isTimeout = str_contains($errorMessage, 'timed out');
                $isSslError = str_contains($errorMessage, 'ssl')
                    || str_contains($errorMessage, 'certificate')
                    || str_contains($errorMessage, 'cURL error 60');
                if ($isSslError) {
                    $failureType = 'ssl_error';
                    $description = 'Fonnte SSL certificate verification failed.';
                } elseif ($isTimeout) {
                    $failureType = 'timeout';
                    $description = 'Fonnte request timed out.';
                } else {
                    $failureType = 'connection_error';
                    $description = 'Could not connect to Fonnte.';
                }
                // Log::error('Fonnte connection exception.', [
                //     'channel' => 'whatsapp',
                //     'token_number' => $tokenNumber,
                //     'failure_type' => $failureType,
                //     'exception' => get_class($e),
                //     'message' => $e->getMessage(),
                //     'previous_exception' => $e->getPrevious()
                //         ? get_class($e->getPrevious())
                //         : null,
                //     'previous_message' => $e->getPrevious()?->getMessage(),
                // ]);
                $lastFailure = new WhatsAppDeliveryException(
                    $failureType,
                    $description
                );
            } catch (RequestException $e) {
                $lastFailure = new WhatsAppDeliveryException(
                    'http_error',
                    'Fonnte returned an unsuccessful HTTP response.',
                    $e->response->status()
                );
            } catch (Throwable $e) {
                // Log::error('Unexpected exception during Fonnte delivery.', [
                //     'channel' => 'whatsapp',
                //     'token_number' => $tokenNumber,
                //     'exception' => get_class($e),
                //     'message' => $e->getMessage(),
                //     'file' => $e->getFile(),
                //     'line' => $e->getLine(),
                // ]);
                $lastFailure = new WhatsAppDeliveryException(
                    'exception',
                    'An unexpected error occurred during WhatsApp delivery.'
                );
            }
        }
        /*
        |--------------------------------------------------------------------------
        | 8. Seluruh token gagal
        |--------------------------------------------------------------------------
        */
        Log::error('WhatsApp delivery failed after all Fonnte tokens were attempted.', [
            'channel' => 'whatsapp',
            'total_tokens' => count($tokens),
            'last_failure_type' => $lastFailure
                ? $lastFailure->getMessage()
                : 'unknown',
            'phone_suffix' => substr($no_hp, -4),
        ]);
        throw $lastFailure ?? new WhatsAppDeliveryException(
            'provider_rejected',
            'Fonnte rejected the WhatsApp delivery.'
        );
    }
    /**
     * Normalisasi nomor WhatsApp.
     */
    private function normalizePhone(string $no_hp): ?string
    {
        $no_hp = preg_replace('/\D+/', '', trim($no_hp));
        if (!$no_hp) {
            return null;
        }
        if (str_starts_with($no_hp, '620')) {
            $no_hp = '62' . substr($no_hp, 3);
        } elseif (str_starts_with($no_hp, '0')) {
            $no_hp = '62' . substr($no_hp, 1);
        } elseif (!str_starts_with($no_hp, '62')) {
            $no_hp = '62' . $no_hp;
        }
        if (!preg_match('/^62\d{9,13}$/', $no_hp)) {
            return null;
        }
        return $no_hp;
    }
}

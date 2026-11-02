<?php
namespace App\Services;
use App\Helpers\DeviceHelper;
use App\Mail\EmailOtpMail;
use App\Models\User;
use App\Repositories\AuthRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
class AuthService
{
    private UserRepository $user_repository;
    private AuthRepository $auth_repository;
    private const OTP_VALID_MINUTES = 2;
    private const OTP_RESEND_SECONDS = 60;
    private const OTP_MAX_ATTEMPTS = 5;
    public function __construct(
        UserRepository $user_repository,
        AuthRepository $auth_repository
    ) {
        $this->user_repository = $user_repository;
        $this->auth_repository = $auth_repository;
    }
    /**
     * Memvalidasi username, password, unit kerja, dan perangkat.
     * Jika 2FA aktif, login menunggu verifikasi OTP.
     */
    public function login(array $data): User|array
    {
        $user = $this->user_repository->getByUsername(
            $data['username']
        );
        if (! $user) {
            throw ValidationException::withMessages([
                'username' => 'Username tidak ditemukan.',
            ]);
        }
        if (! $user->kode_unit_kerja || ! $user->department) {
            throw ValidationException::withMessages([
                'username' => 'Unit Kerja akun tidak valid.',
            ]);
        }
        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password salah.',
            ]);
        }
        $this->validateDeviceLimit($user);
        if ($user->email_2fa_enabled) {
            if (! $user->email) {
                throw ValidationException::withMessages([
                    'username' => 'Email akun belum tersedia. Hubungi administrator.',
                ]);
            }
            request()->session()->put([
                'sigap_2fa_user_id' => $user->id,
                'sigap_2fa_remember' => ! empty($data['remember']),
                'sigap_2fa_method' => 'email',
            ]);
            try {
                $this->sendEmailOtp($user);
            } catch (\Throwable $exception) {
                $this->clearTwoFactorChallenge();
                throw $exception;
            }
            return ['requires_two_factor' => true];
        }
        return $this->completeLogin(
            $user,
            ! empty($data['remember'])
        );
    }
    /**
     * Mengirim OTP melalui email.
     * OTP berlaku 2 menit dan pengiriman ulang dibatasi 60 detik.
     */
    public function sendEmailOtp(User $user): void
    {
        if (! $user->email || ! $user->email_2fa_enabled) {
            throw ValidationException::withMessages([
                'code' => 'Email atau konfigurasi 2FA tidak valid.',
            ]);
        }
        $resendKey = $this->otpResendKey($user);
        if (RateLimiter::tooManyAttempts($resendKey, 1)) {
            $seconds = RateLimiter::availableIn($resendKey);
            throw ValidationException::withMessages([
                'code' => "Silakan tunggu {$seconds} detik sebelum meminta kode baru.",
            ]);
        }
        $code = (string) random_int(100000, 999999);
        try {
            Mail::to($user->email)->send(
                new EmailOtpMail(
                    code: $code,
                    nama: $user->nama ?? $user->username
                )
            );
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'code' => 'Kode OTP gagal dikirim. Silakan coba kembali.',
            ]);
        }
        $expiresAt = now()->addMinutes(self::OTP_VALID_MINUTES);
        Cache::put(
            $this->otpCacheKey($user),
            [
                'hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => $expiresAt->timestamp,
            ],
            $expiresAt
        );
        RateLimiter::hit(
            $resendKey,
            self::OTP_RESEND_SECONDS
        );
        request()->session()->put(
            'sigap_2fa_resend_at',
            now()->addSeconds(self::OTP_RESEND_SECONDS)->timestamp
        );
    }
    /**
     * Menghasilkan cache key OTP berdasarkan user dan sesi login.
     */
    public function otpCacheKey(User $user): string
    {
        return 'sigap_email_2fa_' . hash(
            'sha256',
            $user->id . '|' . request()->session()->getId()
        );
    }
    /**
     * Menghasilkan rate limit key pengiriman ulang OTP.
     */
    public function otpResendKey(User $user): string
    {
        return 'sigap_email_2fa_resend_' . hash(
            'sha256',
            $user->id . '|' . request()->session()->getId()
        );
    }
    /**
     * Menghitung sisa waktu sebelum OTP dapat dikirim ulang.
     */
    public function emailOtpResendWait(): int
    {
        $resendAt = (int) request()->session()->get(
            'sigap_2fa_resend_at',
            0
        );
        return max(0, $resendAt - now()->timestamp);
    }
    /**
     * Memastikan akun tidak melebihi dua perangkat.
     */
    private function validateDeviceLimit(User $user): void
    {
        $fingerprint = DeviceHelper::fingerprint();
        $device = $this->auth_repository->getDeviceByFingerprint(
            $user->id,
            $fingerprint
        );
        if (! $device) {
            $jumlahPerangkat = $this->auth_repository->countUserDevices(
                $user->id
            );
            if ($jumlahPerangkat >= 2) {
                throw ValidationException::withMessages([
                    'username' => 'Akun ini sudah digunakan pada maksimal 2 perangkat.',
                ]);
            }
        }
    }
    /**
     * Menyelesaikan login setelah autentikasi berhasil.
     */
    public function completeLogin(
        User $user,
        bool $remember = false
    ): User {
        $this->validateDeviceLimit($user);
        Auth::login($user, $remember);
        request()->session()->regenerate();
        $fingerprint = DeviceHelper::fingerprint();
        $this->auth_repository->updateOrCreateDevice(
            $user->id,
            $fingerprint,
            [
                'nama_perangkat' => null,
                'browser' => request()->userAgent(),
                'platform' => null,
                'ip_address' => request()->ip(),
                'session_id' => request()->session()->getId(),
                'aktivitas_terakhir' => now(),
            ]
        );
        return $user;
    }
    /**
     * Membatalkan proses login yang menunggu OTP.
     */
    public function clearTwoFactorChallenge(): void
    {
        $userId = request()->session()->get('sigap_2fa_user_id');
        if ($userId) {
            $user = $this->auth_repository->getUserById($userId);
            if ($user) {
                Cache::forget($this->otpCacheKey($user));
            }
        }
        request()->session()->forget([
            'sigap_2fa_user_id',
            'sigap_2fa_remember',
            'sigap_2fa_method',
            'sigap_2fa_resend_at',
        ]);
    }
    /**
     * Proses logout.
     */
    public function logout(): void
    {
        $sessionId = request()->session()->getId();
        $this->auth_repository->deleteDeviceBySession(
            $sessionId
        );
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}

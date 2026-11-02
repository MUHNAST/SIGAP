<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private AuthService $auth_service,
        private ActivityLogService $activity_log_service,
    ) {
    }
    /**
     * Menampilkan halaman verifikasi OTP email.
     */
    public function create(Request $request)
    {
        if (
            ! $request->session()->has('sigap_2fa_user_id') ||
            $request->session()->get('sigap_2fa_method') !== 'email'
        ) {
            return redirect()->route('login')
                ->withErrors([
                    'username' => 'Silakan login kembali.',
                ]);
        }
        return view('auth.two-factor-challenge', [
            'resendWait' => $this->auth_service->emailOtpResendWait(),
        ]);
    }
    /**
     * Memverifikasi OTP email.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Masukkan kode OTP dari email.',
            'code.digits' => 'Kode OTP harus terdiri dari 6 digit.',
        ]);
        $userId = $request->session()->get('sigap_2fa_user_id');
        if (
            ! $userId ||
            $request->session()->get('sigap_2fa_method') !== 'email'
        ) {
            return redirect()->route('login')
                ->withErrors([
                    'username' => 'Sesi verifikasi berakhir. Silakan login kembali.',
                ]);
        }
        $user = User::find($userId);
        if (! $user || ! $user->email_2fa_enabled || ! $user->email) {
            $this->auth_service->clearTwoFactorChallenge();
            return redirect()->route('login')
                ->withErrors([
                    'username' => 'Verifikasi email tidak dapat dilanjutkan.',
                ]);
        }
        $cacheKey = $this->auth_service->otpCacheKey($user);
        $challenge = Cache::get($cacheKey);
        // Periksa keberadaan dan masa berlaku OTP.
        if (
            ! is_array($challenge) ||
            ! isset($challenge['hash'], $challenge['expires_at']) ||
            now()->timestamp >= (int) $challenge['expires_at']
        ) {
            Cache::forget($cacheKey);
            return back()->withErrors([
                'code' => 'Kode OTP sudah kedaluwarsa. Silakan kirim kode baru.',
            ]);
        }
        // Batasi maksimal lima kali percobaan.
        if (($challenge['attempts'] ?? 0) >= 5) {
            $this->auth_service->clearTwoFactorChallenge();
            return redirect()->route('login')
                ->withErrors([
                    'username' => 'Batas percobaan OTP tercapai. Silakan login kembali.',
                ]);
        }
        // Verifikasi kode OTP.
        if (! Hash::check($request->input('code'), $challenge['hash'])) {
            $challenge['attempts']++;
            if ($challenge['attempts'] >= 5) {
                $this->auth_service->clearTwoFactorChallenge();
                return redirect()->route('login')
                    ->withErrors([
                        'username' => 'Batas percobaan OTP tercapai. Silakan login kembali.',
                    ]);
            }
            // Pertahankan waktu kedaluwarsa asli.
            $remainingSeconds = max(
                1,
                (int) $challenge['expires_at'] - now()->timestamp
            );
            Cache::put(
                $cacheKey,
                $challenge,
                now()->addSeconds($remainingSeconds)
            );
            throw ValidationException::withMessages([
                'code' => 'Kode OTP salah. Silakan periksa email Anda.',
            ]);
        }
        // OTP berhasil diverifikasi.
        Cache::forget($cacheKey);
        // Ambil status remember sebelum session challenge dihapus.
        $remember = (bool) $request->session()->get(
            'sigap_2fa_remember',
            false
        );
        // Bersihkan data challenge OTP.
        $this->auth_service->clearTwoFactorChallenge();
        // Selesaikan proses login.
        $user = $this->auth_service->completeLogin(
            $user,
            $remember
        );
        // Tentukan dashboard berdasarkan role pengguna.
        $dashboardRoute = match ($user->role) {
            'super_admin', 'admin' => 'dashboard',
            'user' => 'dashboard_user',
            default => null,
        };
        // Tolak role yang tidak dikenali.
        if ($dashboardRoute === null) {
            $this->auth_service->logout();
            return redirect()->route('login')
                ->withErrors([
                    'username' => 'Role akun tidak memiliki akses ke sistem.',
                ]);
        }
        // Catat aktivitas login setelah autentikasi berhasil.
        $this->activity_log_service->log(
            'Login',
            sprintf(
                '%s login sebagai %s',
                $user->nama,
                str($user->role)->replace('_', ' ')->title()
            )
        );
        // Arahkan pengguna sesuai role.
        return redirect()->route($dashboardRoute)->with([
            'show_welcome' => true,
            'welcome_message' => 'Selamat Datang ' . $user->nama,
        ]);
    }
    /**
     * Mengirim ulang OTP setelah cooldown 60 detik.
     */
    public function resend(Request $request)
    {
        $userId = $request->session()->get('sigap_2fa_user_id');
        if (
            ! $userId ||
            $request->session()->get('sigap_2fa_method') !== 'email'
        ) {
            return redirect()->route('login')
                ->withErrors([
                    'username' => 'Sesi verifikasi berakhir. Silakan login kembali.',
                ]);
        }
        $user = User::find($userId);
        if (! $user || ! $user->email_2fa_enabled || ! $user->email) {
            $this->auth_service->clearTwoFactorChallenge();
            return redirect()->route('login')
                ->withErrors([
                    'username' => 'Email atau konfigurasi 2FA tidak valid.',
                ]);
        }
        $this->auth_service->sendEmailOtp($user);
        return redirect()->route('auth.two-factor.challenge')
            ->with('status', 'Kode OTP baru berhasil dikirim ke email Anda.');
    }
}

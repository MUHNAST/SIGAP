<?php
namespace App\Http\Controllers;
use App\Http\Requests\AuthRequest;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller
{
    private AuthService $auth_service;
    private ActivityLogService $activity_log_service;
    public function __construct(
        AuthService $auth_service,
        ActivityLogService $activity_log_service
    ) {
        $this->auth_service = $auth_service;
        $this->activity_log_service = $activity_log_service;
    }
    public function index()
    {
        $title = 'LOGIN SIGAP';
        return view('auth.index', compact('title'));
    }
    public function login(AuthRequest $request)
    {
        $result = $this->auth_service->login($request->validated());
        // Jika 2FA aktif, arahkan ke verifikasi OTP.
        if (is_array($result) && ($result['requires_two_factor'] ?? false)) {
            return redirect()->route('auth.two-factor.challenge');
        }
        return $this->redirectByRole($result);
    }
    /**
     * Menentukan halaman berdasarkan role pengguna.
     */
    public function redirectByRole($user)
    {
        $dashboardRoute = match ($user->role) {
            'super_admin', 'admin' => 'dashboard',
            'user' => 'dashboard_user',
            default => null,
        };
        if ($dashboardRoute === null) {
            $this->auth_service->logout();
            return redirect()
                ->route('login')
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
        return redirect()->route($dashboardRoute)->with([
            'show_welcome' => true,
            'welcome_message' => 'Selamat Datang ' . $user->nama,
        ]);
    }
    public function logout()
    {
        $user = Auth::user();
        if ($user) {
            $roleLabel = str($user->role)
                ->replace('_', ' ')
                ->title();
            $this->activity_log_service->log(
                'Logout',
                "{$user->nama} logout sebagai {$roleLabel}"
            );
        }
        $this->auth_service->logout();
        return redirect()
            ->route('login')
            ->with('success', 'Logout berhasil');
    }
}

<?php
namespace App\Http\Middleware;
use App\Models\LoginDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
class CheckRole
{
    /**
     * Memeriksa autentikasi dan role pengguna.
     */
    public function handle( Request $request, Closure $next, ...$roles ): Response {
        // Pengguna harus login terlebih dahulu.
        if (! Auth::check()) {
            return redirect()->route('login');
        }
        $user = Auth::user();
        // Perbarui aktivitas perangkat pengguna.
        LoginDevice::where('id_user', $user->id)->where('session_id', $request->session()->getId())
            ->update([
                'aktivitas_terakhir' => now(),
            ]);
        // Role yang diizinkan ditentukan melalui middleware pada route.
        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin.');
        }
        return $next($request);
    }
}

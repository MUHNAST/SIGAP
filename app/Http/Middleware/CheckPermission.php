<?php
namespace App\Http\Middleware;
use App\Models\RolePermission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
class CheckPermission
{
    public function handle( Request $request, Closure $next, string $permission): Response {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        // Super Admin memiliki akses penuh.
        if ($user->role === 'super_admin') {
            return $next($request);
        }
        // Hanya Admin yang dapat menggunakan izin operasional.
        if ($user->role !== 'admin') {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin.');
        }
        // Periksa izin berdasarkan role dan kode permission.
        $hasPermission = RolePermission::query()->where('role', $user->role)
            ->whereHas('permission', function ($query) use ($permission) {
                $query->where('kode', $permission);
            })->exists();
        abort_unless( $hasPermission, 403, 'Akses ditolak: Anda tidak memiliki izin.' );
        return $next($request);
    }
}

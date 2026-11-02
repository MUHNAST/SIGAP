<?php
namespace App\Http\Controllers;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboard_service,
    ) {}
    // Dashboard Admin
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        if (in_array($user->role, ['super_admin', 'admin'])) {
            $title = 'Dashboard Admin';
            $sub_title = 'Ringkasan Sistem Kepegawaian';
            $tahunReminder = $request->get('reminder_year', now()->year);
            $tahunRiwayat  = $request->get('riwayat_year', now()->year);
            return view('dashboard.dashboard_admin',
                array_merge(
                    compact('title', 'sub_title','user'),
                    $this->dashboard_service->getAdminDashboardData($user,$tahunReminder,$tahunRiwayat)
                )
            );
        }
        abort(403, 'Anda tidak memiliki hak akses.');
    }
    // Dashboard umum
    public function dashboard(Request $request)
    {
        $title = 'Dashboard Kepegawaian';
        $sub_title = 'Ringkasan Sistem Kepegawaian';
        $tahunReminder = $request->get('reminder_year', now()->year);
        $tahunRiwayat  = $request->get('riwayat_year', now()->year);
        return view('dashboard.dashboard',
            array_merge(
                compact('title','sub_title'),
                $this->dashboard_service->getPublicDashboardData($tahunReminder,$tahunRiwayat)
            )
        );
    }
    //  Dashboard User.
    public function dashboardUser(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role !== 'user') {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        $title = 'Dashboard Kepegawaian';
        $sub_title = 'Informasi Kepegawaian Pribadi';

        $dashboardData = $this->dashboard_service
            ->getUserDashboardData($user);

        return view(
            'dashboard.dashboard_user',
            array_merge(
                compact('title', 'sub_title', 'user'),
                $dashboardData
            )
        );
    }
}

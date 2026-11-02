<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ActivityLogController extends Controller
{
    private ActivityLogService $activity_log_service;
    public function __construct( ActivityLogService $activity_log_service ) {
        $this->activity_log_service = $activity_log_service;
    }
    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);
        abort_unless(
            in_array($user->role, ['super_admin', 'admin'], true),
            403,
            'Anda tidak memiliki akses.'
        );
        $filters = [
            'search' => $request->input('search'),
            'aktivitas' => $request->input('aktivitas'),
            'tanggal' => $request->input('tanggal'),
        ];
        $data = $this->activity_log_service->getFiltered($filters);
        $statistics = $this->activity_log_service->getStatistics();
        $activities = $this->activity_log_service->getDistinctActivities();
        $title = $user->role === 'super_admin' ? 'Riwayat Aktivitas Seluruh Sistem' : 'Riwayat Aktivitas Unit Kerja';
        return view(
            'activityLog.index',
            array_merge(
                compact(
                    'title',
                    'data',
                    'user',
                    'activities',
                    'filters'
                ),
                $statistics
            )
        );
    }
    public function showForUser(Request $request)
    {
        $loginUser = Auth::user();
        abort_unless($loginUser instanceof User, 401);
        abort_unless($loginUser->role === 'user', 403);
        $filters = [
            'search'  => $request->input('search'),
            'tanggal' => $request->input('tanggal'),
        ];
        // Ambil aktivitas akun yang sedang login saja.
        $data = $this->activity_log_service->getByUserId(
            $loginUser->id,
            $filters
        );
        $nama = $loginUser->employee?->nama
            ?? $loginUser->nama
            ?? 'Pengguna';
        $title = "Riwayat Aktivitas {$nama}";
        return view('activityLog.show', [
            'user' => $loginUser,
            'title' => $title,
            'data' => $data,
            'filters' => $filters,
        ]);
    }
}

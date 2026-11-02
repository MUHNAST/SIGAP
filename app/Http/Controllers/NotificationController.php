<?php
namespace App\Http\Controllers;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notification_service
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        $title = 'Semua Notifikasi';

        $filters = [
            'search'       => $request->input('search'),
            'kategori'     => $request->input('kategori'),
            'sudah_dibaca' => $request->input('sudah_dibaca'),
        ];

        $data = $this->notification_service->getFiltered($filters, $user);

        $statistics = $this->notification_service->getStatistics();

        $summary = $this->notification_service->getSummary();

        if ($user->role === 'user') {
            return view('notification.index_user', [
                'title'      => 'Notifikasi Saya',
                'sub_title'  => 'Daftar notifikasi kepegawaian Anda',
                'data'       => $data,
                'statistics' => $statistics,
                'summary'    => $summary,
                'user'       => $user,
            ]);
        }

        return view('notification.index', [
            'title'      => $title,
            'sub_title'  => 'Daftar seluruh notifikasi sistem kepegawaian',
            'data'       => $data,
            'statistics' => $statistics,
            'summary'    => $summary,
            'user'       => $user,
        ]);
    }

    public function read($id)
    {
        $this->notification_service->markAsRead($id);
        return redirect()->route('notification.index');
    }
    public function unreadCount()
    {
        return response()->json([
            'count' => $this->notification_service->getStatistics()['belumDibaca']
        ]);
    }
    public function history()
    {
        return response()->json(
            $this->notification_service->getHistory()
        );
    }
    public function latest()
    {
        return response()->json(
            $this->notification_service->getLatestNotifications()
        );
    }
    public function markAsRead(int $id)
    {
        $this->notification_service->markAsRead($id);
        return back()->with(
            'success',
            'Notifikasi berhasil ditandai sebagai sudah dibaca.'
        );
    }
    public function markAllAsRead()
    {
        $this->notification_service->markAllAsRead();
        return back()->with(
            'success',
            'Semua notifikasi berhasil ditandai sebagai sudah dibaca.'
        );
    }
}

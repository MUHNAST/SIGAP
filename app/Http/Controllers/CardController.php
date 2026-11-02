<?php
namespace App\Http\Controllers;
use App\Services\CardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class CardController extends Controller
{
    public function __construct(
        private CardService $card_service
    ) {}
    public function index(Request $request,string $jenis) {
        abort_unless( in_array($jenis, ['kp', 'kgb', 'mp']), 404);
        // $user = Auth::user();
        $user = $request->user();
        abort_unless(
            $user && in_array($user->role, ['super_admin', 'admin'], true),
            403,
            'Akses ditolak: Anda tidak memiliki izin.'
        );
        $data = $this->card_service->getUpcoming($jenis,$user);
        $title = $data['title'];
        return view('employee.card-index',compact('data','user','title'));
    }
}

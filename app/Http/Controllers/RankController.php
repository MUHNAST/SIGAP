<?php
namespace App\Http\Controllers;
use App\Http\Requests\RankRequest;
use App\Models\Rank;
use App\Services\ActivityLogService;
use App\Services\RankService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class RankController extends Controller
{
    private RankService $rank_service;
    private ActivityLogService $activity_log_service;
    public function __construct(RankService $rank_service, ActivityLogService $activity_log_service) {
        $this->rank_service = $rank_service;
        $this->activity_log_service = $activity_log_service;
    }
    public function index(){
        $user = Auth::user();
        $title = "Data Pangkat & Golongan PNS";
        $sub_title = 'Kelola Seluruh Data Golongan dan Pangkat PNS Dalam Sistem';
        $data = $this->rank_service->getAll(['*']);
        return view('rank.index', compact('title', 'sub_title', 'data','user'));
    }
    public function store(RankRequest $request)
    {
        $data = $this->rank_service->create($request->validated());
        if ($data) {
            $user = Auth::user();
            $this->activity_log_service->log(
                'Tambah Data Pangkat & Golongan PNS',
                "{$user->nama} Menambahkan Data Pangkat & Golongan PNS '{$data->nama}'."
            );
            return redirect()->route('rank.index')->with('success','Data Pangkat & Golongan PNS berhasil ditambahkan');
        }
        return redirect()->route('rank.index')->with('danger','Data Pangkat & Golongan PNS gagal ditambahkan');
    }
    public function update(Rank $rank, RankRequest $request) {
        $data = $this->rank_service->update($rank, $request->validated());
        // dd($data);
        $user = Auth::user();
        $nama = $user->nama;
        // Simpan Activity Log
        $this->activity_log_service->log(
            'Update Data Pangkat & Golongan PNS',
            "{$nama} Mengedit Data Pangkat & Golongan PNS {$data->nama}",
        );
        if($data){
            return redirect()->route('rank.index')->with('success', 'Data Pangkat & Golongan PNS berhasil diedit');
        }else{
            return redirect()->route('rank.index')->with('danger', 'Data Pangkat & Golongan PNS gagal diedit');
        }
    }
    // public function destroy(Rank $rank)
    // {
    //     $nama = $rank->nama;
    //     $data = $this->rank_service->delete($rank);
    //     $user = Auth::user();
    //     if ($data) {
    //         $this->activity_log_service->log(
    //             'Hapus Data Pangkat & Golongan PNS',
    //             "{$user->nama} menghapus data pangkat & golongan '{$nama}'."
    //         );
    //         return redirect()->route('rank.index')->with('success', 'Data Pangkat & Golongan berhasil dihapus');
    //     }
    //     return redirect()->route('rank.index')->with('danger', 'Data Pangkat & Golongan gagal dihapus');
    // }
}

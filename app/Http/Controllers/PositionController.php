<?php
namespace App\Http\Controllers;
use App\Http\Requests\PositionRequest;
use App\Models\Position;
use App\Services\ActivityLogService;
use App\Services\PositionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
class PositionController extends Controller
{
    private PositionService $position_service;
    private ActivityLogService $activity_log_service;
    public function __construct(PositionService $position_service, ActivityLogService $activity_log_service) {
        $this->position_service = $position_service;
        $this->activity_log_service = $activity_log_service;
    }
    public function index(){
        $user = Auth::user();
        $title = "Data Jabatan";
        $sub_title = "Kelola Seluruh Data Jabatan Dalam Sistem";
        $data = $this->position_service->getAll(['*']);
        return view('position.index', compact('title', 'sub_title', 'data', 'user'));
    }
    // public function show(int $id) {
    //     $title = "Detail Data Jabatan";
    //     $data = $this->position_service->getById($id);
    //     return view('position.detail', compact('title', 'data'));
    // }
    public function store(PositionRequest $request) {
        $data = $this->position_service->create($request->validated());
        $user = Auth::user();
        $nama = $user->nama;
        // Simpan Activity Log
        $this->activity_log_service->log(
            'Tambah Data Jabatan',
            "{$nama} Menambahkan Data Jabatan {$data->nama}",
        );
        if($data){
            return redirect()->route('position.index')->with('success', 'Data Jabatan berhasil ditambahkan');
        }else{
            return redirect()->route('position.index')->with('danger', 'Data Jabatan gagal ditambahkan');
        }
    }
    public function update(Position $position, PositionRequest $request) {
        $data = $this->position_service->update($position, $request->validated());
        $user = Auth::user();
        $nama = $user->nama;
        // Simpan Activity Log
        $this->activity_log_service->log(
            'Update Data Jabatan',
            "{$nama} Mengedit Data Jabatan {$data->nama}",
        );
        if($data){
            return redirect()->route('position.index')->with('success', 'Data Jabatan berhasil diedit');
        }else{
            return redirect()->route('position.index')->with('danger', 'Data Jabatan gagal diedit');
        }
    }
    // public function destroy(string $kode)
    // {
    //     $position = $this->position_service->getByCode($kode);
    //     $data = $this->position_service->delete($kode);
    //     $user = Auth::user();
    //     $nama = $user->nama;
    //     if ($data) {
    //         $this->activity_log_service->log(
    //             'Hapus Data Jabatan',
    //             "{$nama} menghapus data jabatan '{$position->nama}'."
    //         );
    //         return redirect()->route('position.index')->with('success', 'Data Jabatan berhasil dihapus');
    //     }
    //     return redirect()->route('position.index')->with('danger', 'Data Jabatan gagal dihapus');
    // }
}

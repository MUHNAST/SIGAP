<?php
namespace App\Http\Controllers;
use App\Http\Requests\PppkRequest;
use App\Models\Pppk;
use App\Models\Rank;
use App\Services\ActivityLogService;
use App\Services\PppkService;
use Illuminate\Support\Facades\Auth;
class PppkController extends Controller
{
    private PppkService $p3k_service;
    private ActivityLogService $activity_log_service;
    public function __construct(PppkService $p3k_service, ActivityLogService $activity_log_service) {
        $this->p3k_service = $p3k_service;
        $this->activity_log_service = $activity_log_service;
    }
    public function index(){
        $user = Auth::user();
        $title = "Data Golongan PPPK";
        $sub_title = "Kelola Seluruh Data Golongan PPPK Dalam Sistem";
        $data = $this->p3k_service->getAll(['*']);
        return view('pppk.index', compact('title', 'sub_title', 'data','user'));
    }
    public function store(PppkRequest $request)
    {
        $data = $this->p3k_service->create($request->validated());
        if ($data) {
            $user = Auth::user();
            $this->activity_log_service->log(
                'Tambah Data Golongan PPPK',
                "{$user->nama} menambahkan Data Golongan PPPK '{$data->nama}'."
            );
            return redirect()->route('pppk.index')->with('success','Data Golongan PPPK berhasil ditambahkan');
        }
        return redirect()->route('pppk.index')->with('danger','Data Golongan PPPK gagal ditambahkan');
    }
    public function update(Pppk $pppk, PppkRequest $request) {
        $data = $this->p3k_service->update($pppk, $request->validated());
        // dd($data);
        $user = Auth::user();
        $nama = $user->nama;
        // Simpan Activity Log
        $this->activity_log_service->log(
            'Update Data Golongan PPPK',
            "{$nama} Mengedit Data Golongan PPPK {$data->nama}",
        );
        if($data){
            return redirect()->route('pppk.index')->with('success', 'Data Golongan PPPK berhasil diedit');
        }else{
            return redirect()->route('pppk.index')->with('danger', 'Data Golongan PPPK gagal diedit');
        }
    }
    // public function destroy(Pppk $p3k)
    // {
    //     $nama = $p3k->nama;
    //     $data = $this->p3k_service->delete($p3k);
    //     $user = Auth::user();
    //     if ($data) {
    //         $this->activity_log_service->log(
    //             'Hapus Data Golongan PPPK',
    //             "{$user->nama} menghapus Data Golongan PPPK '{$nama}'."
    //         );
    //         return redirect()->route('pppk.index')->with('success', 'Data Golongan PPPK berhasil dihapus');
    //     }
    //     return redirect()->route('pppk.index')->with('danger', 'Data Golongan PPPK gagal dihapus');
    // }
}

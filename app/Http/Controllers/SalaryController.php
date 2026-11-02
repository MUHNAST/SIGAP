<?php
namespace App\Http\Controllers;
use App\Http\Requests\SalaryRequest;
use App\Models\Rank;
use App\Services\ActivityLogService;
use App\Services\SalaryService;
use Illuminate\Support\Facades\Auth;
class SalaryController extends Controller
{
    private SalaryService $salary_service;
    private ActivityLogService $activity_log_service;
    public function __construct(SalaryService $salary_service, ActivityLogService $activity_log_service) {
        $this->salary_service = $salary_service;
        $this->activity_log_service = $activity_log_service;
    }
    public function index()
    {
        $title = 'Data Gaji';
        $sub_title = 'Kelola Seluruh Data Gaji Dalam Sistem';
        $ranks = Rank::orderBy('kode', 'asc')->get();
        $data = $this->salary_service->getAll(['*']);
        $user = Auth::user();
        return view('salary.index', compact('title', 'sub_title', 'data', 'ranks', 'user'));
    }
    public function store(SalaryRequest $request) {
        $data = $this->salary_service->create($request->validated());
        // dd($data);
        $user = Auth::user();
        $nama = $user->nama;;
        // Simpan Activity Log
        $this->activity_log_service->log(
            'Tambah Data Gaji',
            "{$nama} menambahkan data gaji {$data->rank->nama} masa kerja {$data->masa_kerja} tahun."
        );
        if($data){
            return redirect()->route('salary.index')->with('success', 'Data Gaji berhasil ditambahkan');
        }else{
            return redirect()->route('salary.index')->with('danger', 'Data Gaji gagal ditambahkan');
        }
    }
    public function update(int $id, SalaryRequest $request) {
        $data = $this->salary_service->update($id, $request->validated());
        $user = Auth::user();
        // dd($data);
        $nama = $user->nama;;
        // Simpan Activity Log
        $this->activity_log_service->log(
            'Update Data Gaji',
            "{$nama} mengubah data gaji {$data->rank->nama} masa kerja {$data->masa_kerja} tahun."
        );
        if($data){
            return redirect()->route('salary.index')->with('success', 'Data Gaji berhasil diedit');
        }else{
            return redirect()->route('salary.index')->with('danger', 'Data Gaji gagal diedit');
        }
    }
    // public function destroy(int $id)
    // {
    //     $salary = $this->salary_service->getById($id);
    //     $rankName = $salary->rank?->nama ?? '-';
    //     $rankCode = $salary->rank?->kode ?? '-';
    //     $masaKerja = $salary->tahun_masa_kerja;
    //     $gajiPokok = number_format( $salary->gaji_pokok, 0, ',', '.');
    //     $data = $this->salary_service->delete($id);
    //     if ($data) {
    //         $user = Auth::user();
    //         $this->activity_log_service->log(
    //             'Hapus Data Gaji',
    //             "{$user->nama} menghapus data gaji {$rankName} ({$rankCode}) masa kerja {$masaKerja} tahun dengan gaji pokok Rp {$gajiPokok}."
    //         );
    //         return redirect()->route('salary.index')->with('success','Data Gaji berhasil dihapus');
    //     }
    //     return redirect()->route('salary.index')->with('danger','Data Gaji gagal dihapus');
    // }
}

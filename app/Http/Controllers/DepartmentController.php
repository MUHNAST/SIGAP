<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use App\Services\ActivityLogService;
use App\Services\DepartmentService;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    private DepartmentService $department_service;
    private ActivityLogService $activity_log_service;
    public function __construct(DepartmentService $department_service, ActivityLogService $activity_log_service) {
        $this->department_service = $department_service;
        $this->activity_log_service = $activity_log_service;
    }

    public function index(){
        $user = Auth::user();
        $title = "Data Unit Kerja";
        $sub_title = 'Kelola seluruh data unit kerja dalam sistem';
        $data = $this->department_service->getAll(['*']);
        return view('department.index', compact('title', 'sub_title', 'data', 'user'));
    }

    public function store(DepartmentRequest $request) {
        $data = $this->department_service->create($request->validated());
        $user = Auth::user();
        $nama = $user->nama;
        // Simpan Activity Log
        $this->activity_log_service->log(
            'Tambah Data Unit Kerja',
            "{$nama} Menambahkan Data Unit Kerja {$data->nama}",
        );
        if($data){
            return redirect()->route('department.index')->with('success', 'Data unit kerja berhasil ditambahkan');
        }else{
            return redirect()->route('department.index')->with('danger', 'Data unit kerja gagal ditambahkan');
        }
    }

    public function update(Department $department,DepartmentRequest  $request) {
        $data = $this->department_service->update($department, $request->validated());
        $user = Auth::user();
        $nama = $user->nama;
        // Simpan Activity Log
        $this->activity_log_service->log(
            'Update Data Unit Kerja',
            "{$nama} Mengedit Data Unit Kerja {$data->nama}",
        );
        if($data){
            return redirect()->route('department.index')->with('success', 'Data unit kerja berhasil diedit');
        }else{
            return redirect()->route('department.index')->with('danger', 'Data unit kerja gagal diedit');
        }
    }

    // public function destroy(string $kode){
    //     $department = $this->department_service->getByCode($kode);
    //     $data = $this->department_service->delete($kode);
    //     $user = Auth::user();
    //     $nama = $user->nama;
    //     $this->activity_log_service->log(
    //         'Hapus Data Unit Kerja',
    //         "{$nama} Menghapus Data Unit Kerja {$department->nama}",
    //     );
    //     if($data){
    //         return redirect()->route('department.index')->with('success', 'Data unit kerja berhasil dihapus');
    //     }else{
    //         return redirect()->route('department.index')->with('danger', 'Data unit kerja gagal dihapus');
    //     }
    // }
}

<?php
namespace App\Repositories;

use App\Models\Department;

class DepartmentRepository
{
    public function getAll(array $data){
        return Department::select($data)->latest()->get();
    }

    public function getByCode(string $kode, array $data){
        return Department::select($data)->findOrFail($kode);
    }

    public function create(array $data){
        return Department::create($data);
    }

    public function update(string $kode, array $data) {
        $department = Department::findOrFail($kode);
        $department->update($data);
        return $department;
    }

    // public function delete(string $kode){
    //     $department = Department::findOrFail($kode);
    //     return $department->delete();
    // }
}

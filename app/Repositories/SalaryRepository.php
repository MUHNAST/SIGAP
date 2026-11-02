<?php
namespace App\Repositories;

use App\Models\Salary;

class SalaryRepository
{
    // public function getAll(array $data = ['*'])
    // {
    //     return Salary::with('rank')->select($data)->latest()->get();
    // }
    public function getAll(array $data = ['*'])
    {
        return Salary::with('rank')->select($data)->orderBy('id_pangkat', 'asc')->orderBy('tahun_masa_kerja', 'asc')->get();
    }

    public function getById(int $id, array $data = ['*'])
    {
        return Salary::with('rank')->select($data)->findOrFail($id);
    }

    public function create(array $data){
        return Salary::create($data);
    }

    public function update(int $id, array $data) {
        $salary = Salary::findOrFail($id);
        $salary->update($data);
        return $salary;
    }

    public function delete(int $id){
        $salary = Salary::findOrFail($id);
        return $salary->delete();
    }
}

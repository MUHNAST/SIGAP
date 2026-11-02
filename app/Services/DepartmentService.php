<?php

namespace App\Services;

use App\Models\Department;
use App\Repositories\DepartmentRepository;

class DepartmentService  {
    private DepartmentRepository $department_repository;
    public function __construct(DepartmentRepository $department_repository) {
        $this->department_repository = $department_repository;
    }

    public function getAll(array $data = ['*']) {
        return $this->department_repository->getAll($data);
    }

    public function getByCode(string $kode, array $data = ['*']){
        return $this->department_repository->getByCode($kode, $data);
    }

    public function create(array $data) {
        return $this->department_repository->create($data);
    }
    public function update(Department $department, array $data) {
        // unset($data['kode']);
        return $this->department_repository->update($department->kode, $data);
    }

    public function delete(string $kode) {
        return $this->department_repository->delete($kode);
    }

}

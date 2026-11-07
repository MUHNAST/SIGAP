<?php

namespace App\Services;

use App\Models\Pppk;
use App\Repositories\PppkRepository;

class PppkService  {

    private PppkRepository $p3k_repository;
    public function __construct(PppkRepository $p3k_repository) {
        $this->p3k_repository = $p3k_repository;
    }

    public function getAll(array $data = ['*']) {
        return $this->p3k_repository->getAll($data);
    }

    public function getById(string $kode, array $data = ['*']){
        return $this->p3k_repository->getById($kode, $data);
    }

    public function create(array $data) {

        return $this->p3k_repository->create($data);
    }
    public function update(Pppk $p3k, array $data) {
        // dd($data);
        return $this->p3k_repository->update($p3k, $data);
    }

    // public function delete(Pppk $p3k) {
    //     return $this->p3k_repository->delete($p3k);
    // }
}

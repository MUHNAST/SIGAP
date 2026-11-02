<?php

namespace App\Services;

use App\Models\Position;
use App\Repositories\PositionRepository;

class PositionService {

    private PositionRepository $position_repository;
    public function __construct(PositionRepository $position_repository) {
        $this->position_repository = $position_repository;
    }

    public function getAll(array $data = ['*']) {
        return $this->position_repository->getAll($data);
    }

    public function getByCode(string $kode, array $data = ['*']){
        return $this->position_repository->getByCode($kode, $data);
    }

    public function create(array $data) {
        return $this->position_repository->create($data);
    }
    public function update(Position $position, array $data) : Position {
        return $this->position_repository->update($position->kode, $data);
    }

    public function delete(string $kode) {
        return $this->position_repository->delete($kode);
    }

}

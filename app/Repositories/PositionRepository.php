<?php
namespace App\Repositories;

use App\Models\Position;

class PositionRepository
{
    public function getAll(array $data){
        return Position::select($data)->latest()->get();
    }

    public function getByCode(string $kode, array $data){
        return Position::select($data)->findOrFail($kode);
    }

    public function create(array $data){
        return Position::create($data);
    }

    public function update(string $kode, array $data) {
        $position = Position::findOrFail($kode);
        $position->update($data);
        return $position;
    }

    public function delete(string $kode){
        $position = Position::findOrFail($kode);
        return $position->delete();
    }
}

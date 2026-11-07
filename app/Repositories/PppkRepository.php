<?php
namespace App\Repositories;

use App\Models\Pppk;

class PppkRepository
{
    public function getAll(array $data = ['*'])
    {
        return Pppk::select($data)->orderBy('kode', 'asc')->get();
    }

    public function getById(string $kode, array $data){
        return Pppk::select($data)->findOrFail($kode);
    }

    public function create(array $data){
        return Pppk::create($data);
    }

    public function update(Pppk $P3k, array $data) {
        $P3k->update($data);
        return $P3k->refresh();
    }

    // public function delete(Pppk $P3k)
    // {
    //     return $P3k->delete();
    // }
}

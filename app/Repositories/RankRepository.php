<?php
namespace App\Repositories;

use App\Models\Rank;

class RankRepository
{
    public function getAll(array $data = ['*'])
    {
        return Rank::select($data)->orderBy('kode', 'asc')->get();
    }

    public function getById(int $id, array $data){
        return Rank::select($data)->findOrFail($id);
    }

    public function create(array $data){
        return Rank::create($data);
    }

    public function update(Rank $rank, array $data) {
        $rank->update($data);
        return $rank->refresh();
    }

    public function delete(Rank $rank)
    {
        return $rank->delete();
    }
}

<?php

namespace App\Services;

use App\Models\Rank;
use App\Repositories\RankRepository;

class RankService  {

    private RankRepository $rank_repository;
    public function __construct(RankRepository $rank_repository) {
        $this->rank_repository = $rank_repository;
    }

    public function getAll(array $data = ['*']) {
        return $this->rank_repository->getAll($data);
    }

    public function getById(int $id, array $data = ['*']){
        return $this->rank_repository->getById($id, $data);
    }

    public function create(array $data) {

        return $this->rank_repository->create($data);
    }
    public function update(Rank $rank, array $data) {
        // dd($data);
        return $this->rank_repository->update($rank, $data);
    }

    // public function delete(Rank $rank) {
    //     return $this->rank_repository->delete($rank);
    // }
}

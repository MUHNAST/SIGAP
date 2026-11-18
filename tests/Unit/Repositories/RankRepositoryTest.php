<?php

namespace Tests\Unit\Repositories;

use App\Models\Rank;
use App\Repositories\RankRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_rank_repository_reads_creates_updates_and_deletes_records(): void
    {
        $repository = new RankRepository();
        $rankB = Rank::create([
            'kode' => 'III/b',
            'nama' => 'Penata Muda Tingkat I',
            'urutan_pangkat' => 10,
        ]);
        $rankA = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);

        $this->assertSame(
            ['III/a', 'III/b'],
            $repository->getAll(['kode'])->pluck('kode')->all()
        );
        $this->assertSame($rankA->id, $repository->getById($rankA->id, ['*'])->id);

        $created = $repository->create([
            'kode' => 'IV/a',
            'nama' => 'Pembina',
            'urutan_pangkat' => 13,
        ]);
        $updated = $repository->update($created, ['nama' => 'Pembina Tingkat I']);

        $this->assertSame('Pembina Tingkat I', $updated->nama);
        $this->assertTrue($repository->delete($rankB));
        $this->assertDatabaseMissing('ranks', ['id' => $rankB->id]);
    }

    public function test_rank_repository_throws_when_requested_id_is_missing(): void
    {
        $this->expectException(ModelNotFoundException::class);

        (new RankRepository())->getById(999999, ['*']);
    }
}

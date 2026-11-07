<?php

namespace Tests\Unit\Services;

use App\Repositories\SalaryRepository;
use App\Services\SalaryService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class SalaryServiceTest extends TestCase
{
    private SalaryRepository $repository;
    private SalaryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(SalaryRepository::class);
        $this->service = new SalaryService($this->repository);
    }

    /**
     * Memeriksa validasi melempar exception saat gaji pokok bernilai negatif (< 0).
     */
    public function test_create_gaji_melempar_validasi_jika_gaji_pokok_negatif(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->service->create([
                'id_pangkat' => 1,
                'gaji_pokok' => -500000,
                'tahun_masa_kerja' => 2,
            ]);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('gaji_pokok', $e->errors());
            $this->assertSame('Gaji pokok tidak boleh kurang dari 0.', $e->errors()['gaji_pokok'][0]);
            throw $e;
        }
    }

    /**
     * Memeriksa validasi melempar exception saat masa kerja bernilai negatif (< 0).
     */
    public function test_create_gaji_melempar_validasi_jika_masa_kerja_negatif(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->service->create([
                'id_pangkat' => 1,
                'gaji_pokok' => 3000000,
                'tahun_masa_kerja' => -1,
            ]);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('tahun_masa_kerja', $e->errors());
            $this->assertSame('Masa kerja tidak boleh kurang dari 0.', $e->errors()['tahun_masa_kerja'][0]);
            throw $e;
        }
    }

    /**
     * Memeriksa alur create berhasil memanggil repository saat data valid.
     */
    public function test_create_gaji_berhasil_saat_data_valid(): void
    {
        $payload = [
            'id_pangkat' => 1,
            'gaji_pokok' => 3500000,
            'tahun_masa_kerja' => 4,
        ];

        $dummySalary = (object) array_merge(['id' => 10], $payload);

        $this->repository->shouldReceive('create')
            ->once()
            ->with($payload)
            ->andReturn($dummySalary);

        $result = $this->service->create($payload);

        $this->assertSame(10, $result->id);
        $this->assertSame(3500000, $result->gaji_pokok);
    }

    /**
     * Memeriksa update melempar exception saat gaji pokok bernilai negatif.
     */
    public function test_update_gaji_melempar_validasi_jika_gaji_pokok_negatif(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->update(1, [
            'gaji_pokok' => -100,
            'tahun_masa_kerja' => 2,
        ]);
    }

    /**
     * Memeriksa update berhasil memanggil repository saat data valid.
     */
    public function test_update_gaji_berhasil_saat_data_valid(): void
    {
        $payload = [
            'gaji_pokok' => 4000000,
            'tahun_masa_kerja' => 6,
        ];

        $dummySalary = (object) array_merge(['id' => 1], $payload);

        $this->repository->shouldReceive('update')
            ->once()
            ->with(1, $payload)
            ->andReturn($dummySalary);

        $result = $this->service->update(1, $payload);

        $this->assertSame(4000000, $result->gaji_pokok);
    }

    public function test_get_all_and_get_by_id_delegate_to_repository(): void
    {
        $rows = [(object) ['id' => 1]];
        $row = (object) ['id' => 2];

        $this->repository->shouldReceive('getAll')
            ->once()
            ->with(['*'])
            ->andReturn($rows);
        $this->repository->shouldReceive('getById')
            ->once()
            ->with(2, ['*'])
            ->andReturn($row);

        $this->assertSame($rows, $this->service->getAll());
        $this->assertSame($row, $this->service->getById(2));
    }
}

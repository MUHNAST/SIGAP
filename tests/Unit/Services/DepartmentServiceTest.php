<?php

namespace Tests\Unit\Services;

use App\Models\Department;
use App\Repositories\DepartmentRepository;
use App\Services\DepartmentService;
use Mockery;
use Tests\TestCase;

class DepartmentServiceTest extends TestCase
{
    public function test_department_service_delegates_reads_create_and_update_to_repository(): void
    {
        $repository = Mockery::mock(DepartmentRepository::class);
        $service = new DepartmentService($repository);
        $department = new Department(['kode' => 'UNIT-01', 'nama' => 'Unit A']);
        $data = ['kode' => 'UNIT-01', 'nama' => 'Unit A'];

        $repository->shouldReceive('getAll')->once()->with(['kode'])->andReturn(collect([$department]));
        $repository->shouldReceive('getByCode')->once()->with('UNIT-01', ['nama'])->andReturn($department);
        $repository->shouldReceive('create')->once()->with($data)->andReturn($department);
        $repository->shouldReceive('update')->once()->with('UNIT-01', ['nama' => 'Unit Baru'])->andReturn($department);
        $this->assertSame([$department], $service->getAll(['kode'])->all());
        $this->assertSame($department, $service->getByCode('UNIT-01', ['nama']));
        $this->assertSame($department, $service->create($data));
        $this->assertSame($department, $service->update($department, ['nama' => 'Unit Baru']));
    }
}

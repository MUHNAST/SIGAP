<?php

namespace Tests\Unit\Repositories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\DepartmentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DepartmentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DepartmentRepository();
    }

    public function test_get_all_returns_departments_for_requested_columns(): void
    {
        Department::create(['kode' => 'UNIT-01', 'nama' => 'Unit A', 'status' => true]);
        Department::create(['kode' => 'UNIT-02', 'nama' => 'Unit B', 'status' => false]);

        $departments = $this->repository->getAll(['kode', 'nama']);

        $this->assertCount(2, $departments);
        $this->assertEqualsCanonicalizing(['UNIT-01', 'UNIT-02'], $departments->pluck('kode')->all());
        $this->assertSame('Unit A', $this->repository->getByCode('UNIT-01', ['nama'])->nama);
    }

    public function test_get_by_code_throws_for_missing_department(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->repository->getByCode('MISSING', ['*']);
    }

    public function test_create_and_update_persist_department_data(): void
    {
        $department = $this->repository->create([
            'kode' => 'UNIT-01',
            'nama' => 'Unit A',
            'deskripsi' => null,
            'status' => true,
        ]);
        $updated = $this->repository->update('UNIT-01', [
            'nama' => 'Unit Baru',
            'status' => false,
        ]);

        $this->assertSame('UNIT-01', $department->kode);
        $this->assertSame('Unit Baru', $updated->nama);
        $this->assertFalse((bool) $updated->status);
    }

    public function test_update_throws_for_missing_department(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->repository->update('MISSING', ['nama' => 'No Unit']);
    }

    public function test_department_relations_resolve_employees_and_admin_users(): void
    {
        $department = Department::create([
            'kode' => 'UNIT-01',
            'nama' => 'Unit A',
            'status' => true,
        ]);
        $employee = Employee::create([
            'nip' => '199001012020011001',
            'nama' => 'Pegawai A',
            'kode_unit_kerja' => 'UNIT-01',
        ]);
        $user = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-01',
        ]);

        $this->assertSame([$employee->nip], $department->employees->pluck('nip')->all());
        $this->assertSame([$user->id], $department->users->pluck('id')->all());
    }
}

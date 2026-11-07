<?php

namespace Tests\Unit\Repositories;

use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\Pppk;
use App\Models\Rank;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployeeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EmployeeRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EmployeeRepository();
    }

    /**
     * Memeriksa employeeQuery membatasi data berdasarkan role (super_admin, admin, user).
     */
    public function test_employee_query_membatasi_data_berdasarkan_role(): void
    {
        $reflection = new \ReflectionClass(EmployeeRepository::class);
        $method = $reflection->getMethod('employeeQuery');
        $method->setAccessible(true);

        // 1. Super Admin: tidak ada klausul pembatasan unit kerja
        $superAdmin = new User(['role' => 'super_admin']);
        $querySuperAdmin = $method->invoke($this->repository, $superAdmin);
        $this->assertStringNotContainsString('kode_unit_kerja', $querySuperAdmin->toSql());

        // 2. Admin: terdapat klausul where kode_unit_kerja
        $admin = new User(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-01']);
        $queryAdmin = $method->invoke($this->repository, $admin);
        $this->assertStringContainsString('kode_unit_kerja', $queryAdmin->toSql());

        // 3. User: terdapat klausul where nip
        $user = new User(['role' => 'user', 'nip' => '199001012015011001']);
        $queryUser = $method->invoke($this->repository, $user);
        $this->assertStringContainsString('nip', $queryUser->toSql());

        // 4. User tanpa NIP melempar AuthorizationException
        $userTanpaNip = new User(['role' => 'user', 'nip' => null]);
        try {
            $method->invoke($this->repository, $userTanpaNip);
            $this->fail('Seharusnya melempar AuthorizationException');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('Akun pengguna belum terhubung dengan data pegawai.', $e->getMessage());
        }

        // 5. Role tidak valid melempar AuthorizationException
        $guestUser = new User(['role' => 'guest']);
        $this->expectException(AuthorizationException::class);
        $method->invoke($this->repository, $guestUser);
    }

    /**
     * Memeriksa getRanksByEducation memfilter golongan PPPK berdasarkan rentang pendidikan.
     */
    public function test_get_ranks_by_education_memfilter_pppk_berdasarkan_tingkat_pendidikan(): void
    {
        EducationLevel::create([
            'kode' => 'S1',
            'nama' => 'Sarjana',
            'status' => true,
            'golongan_pppk_minimum' => 9,
            'golongan_pppk_maksimum' => 11,
            'pangkat_pns_minimum' => 9,
            'pangkat_pns_maksimum' => 12,
        ]);

        Pppk::create(['kode' => 'IX', 'nama' => 'Golongan IX', 'urutan_golongan' => 9]);
        Pppk::create(['kode' => 'X', 'nama' => 'Golongan X', 'urutan_golongan' => 10]);
        Pppk::create(['kode' => 'XI', 'nama' => 'Golongan XI', 'urutan_golongan' => 11]);
        Pppk::create(['kode' => 'XII', 'nama' => 'Golongan XII', 'urutan_golongan' => 12]);

        $ranks = $this->repository->getRanksByEducation('S1', 'PPPK');

        $this->assertCount(3, $ranks);
        $this->assertTrue($ranks->contains('kode', 'IX'));
        $this->assertTrue($ranks->contains('kode', 'X'));
        $this->assertTrue($ranks->contains('kode', 'XI'));
        $this->assertFalse($ranks->contains('kode', 'XII')); // Di luar batas maksimum
    }

    /**
     * Memeriksa getRanksByEducation menambah batas maksimum 1 tingkat untuk pendidikan PNS D3/S1/S2/S3.
     */
    public function test_get_ranks_by_education_menambah_satu_tingkat_maksimum_pns_untuk_d3_ke_atas(): void
    {
        EducationLevel::create([
            'kode' => 'S1',
            'nama' => 'Sarjana',
            'status' => true,
            'pangkat_pns_minimum' => 9,  // III/a
            'pangkat_pns_maksimum' => 11, // III/c (akan ditambah 1 tingkat menjadi 12 = III/d)
        ]);

        Rank::create(['kode' => 'III/a', 'nama' => 'Penata Muda', 'urutan_pangkat' => 9]);
        Rank::create(['kode' => 'III/b', 'nama' => 'Penata Muda Tk. I', 'urutan_pangkat' => 10]);
        Rank::create(['kode' => 'III/c', 'nama' => 'Penata', 'urutan_pangkat' => 11]);
        Rank::create(['kode' => 'III/d', 'nama' => 'Penata Tk. I', 'urutan_pangkat' => 12]);
        Rank::create(['kode' => 'IV/a', 'nama' => 'Pembina', 'urutan_pangkat' => 13]);

        $ranks = $this->repository->getRanksByEducation('S1', 'PNS');

        // Batas maksimum awal 11 dinaikkan menjadi 12, jadi III/a, III/b, III/c, III/d masuk (4 data)
        $this->assertCount(4, $ranks);
        $this->assertTrue($ranks->contains('kode', 'III/d'));
        $this->assertFalse($ranks->contains('kode', 'IV/a'));
    }

    /**
     * Memeriksa getRanksByEducation mengembalikan koleksi kosong jika parameter null atau tidak aktif.
     */
    public function test_get_ranks_by_education_mengembalikan_kosong_jika_parameter_tidak_valid(): void
    {
        $this->assertEmpty($this->repository->getRanksByEducation(null, 'PNS'));
        $this->assertEmpty($this->repository->getRanksByEducation('S1', null));
        $this->assertEmpty($this->repository->getRanksByEducation('NON_EXISTENT', 'PNS'));
    }

    /**
     * Memeriksa updateOrCreate melempar ValidationException jika Admin mencoba mendaftarkan NIP pada unit lain.
     */
    public function test_update_or_create_menolak_admin_jika_nip_terdaftar_pada_unit_lain(): void
    {
        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit Kerja A', 'status' => true]);
        Department::create(['kode' => 'UNIT-B', 'nama' => 'Unit Kerja B', 'status' => true]);

        $admin = new User(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-A']);

        Employee::create([
            'nip' => '199001012015011001',
            'nama' => 'Pegawai Unit B',
            'kode_unit_kerja' => 'UNIT-B',
        ]);

        $this->expectException(ValidationException::class);

        $this->repository->updateOrCreate(
            ['nip' => '199001012015011001'],
            ['nama' => 'Pegawai Diedit'],
            $admin
        );
    }

    public function test_repository_reads_are_scoped_and_export_requires_management_role(): void
    {
        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        Department::create(['kode' => 'UNIT-B', 'nama' => 'Unit B', 'status' => true]);
        Employee::create([
            'nip' => '199001012015011001',
            'nama' => 'Employee A',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        Employee::create([
            'nip' => '199001012015011002',
            'nama' => 'Employee B',
            'kode_unit_kerja' => 'UNIT-B',
        ]);
        $admin = new User(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-A']);

        $this->assertSame(
            ['199001012015011001'],
            $this->repository->getAll($admin)->pluck('nip')->all()
        );
        $this->assertSame(
            ['199001012015011001'],
            $this->repository->getExportData($admin)->pluck('nip')->all()
        );
        $this->expectException(AuthorizationException::class);
        $this->repository->getExportData(new User(['role' => 'user']));
    }

    public function test_get_by_id_keeps_nip_when_selecting_specific_columns(): void
    {
        Employee::create([
            'nip' => '199001012015011003',
            'nama' => 'Employee C',
        ]);
        $user = new User(['role' => 'super_admin']);

        $employee = $this->repository->getById(
            '199001012015011003',
            $user,
            ['nama']
        );

        $this->assertSame('199001012015011003', $employee->nip);
        $this->assertSame('Employee C', $employee->nama);
        $this->expectException(ModelNotFoundException::class);
        $this->repository->getById('199001012015011099', $user);
    }

    public function test_create_update_and_delete_enforce_roles_and_admin_unit(): void
    {
        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        Department::create(['kode' => 'UNIT-B', 'nama' => 'Unit B', 'status' => true]);
        $admin = new User(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-A']);
        $employee = $this->repository->create([
            'nip' => '199001012015011004',
            'nama' => 'Employee D',
            'kode_unit_kerja' => 'UNIT-B',
        ], $admin);
        $this->assertSame('UNIT-A', $employee->kode_unit_kerja);

        $updated = $this->repository->update(
            $employee->nip,
            ['nama' => 'Updated', 'kode_unit_kerja' => 'UNIT-B'],
            $admin
        );
        $this->assertSame('Updated', $updated->nama);
        $this->assertSame('UNIT-A', $updated->kode_unit_kerja);

        $this->expectException(AuthorizationException::class);
        $this->repository->delete($employee->nip, $admin);
    }

    public function test_super_admin_can_delete_employee(): void
    {
        $employee = Employee::create([
            'nip' => '199001012015011005',
            'nama' => 'Employee E',
        ]);

        $this->assertTrue(
            $this->repository->delete(
                $employee->nip,
                new User(['role' => 'super_admin'])
            )
        );
        $this->assertDatabaseMissing('employees', ['nip' => $employee->nip]);
    }

    public function test_master_data_uses_role_scoped_departments(): void
    {
        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        Department::create(['kode' => 'UNIT-B', 'nama' => 'Unit B', 'status' => true]);

        $adminData = $this->repository->getMasterData(
            new User(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-A'])
        );
        $this->assertSame(['UNIT-A'], $adminData['departments']->pluck('kode')->all());

        $superAdminData = $this->repository->getMasterData(
            new User(['role' => 'super_admin'])
        );
        $this->assertCount(2, $superAdminData['departments']);

        $this->expectException(AuthorizationException::class);
        $this->repository->getMasterData(new User(['role' => 'guest']));
    }
}

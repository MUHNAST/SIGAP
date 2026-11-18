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
        Department::create(['kode' => 'UNIT-01', 'nama' => 'Unit 01', 'status' => true]);
        Department::create(['kode' => 'UNIT-02', 'nama' => 'Unit 02', 'status' => true]);
        Employee::create([
            'nip' => '199001012015011001',
            'nama' => 'Pegawai Unit 01',
            'kode_unit_kerja' => 'UNIT-01',
        ]);
        Employee::create([
            'nip' => '199001012015011002',
            'nama' => 'Pegawai Unit 02',
            'kode_unit_kerja' => 'UNIT-02',
        ]);

        $superAdmin = new User(['role' => 'super_admin']);
        $admin = new User(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-01']);
        $user = new User(['role' => 'user', 'nip' => '199001012015011001']);

        $this->assertEqualsCanonicalizing(
            ['199001012015011002', '199001012015011001'],
            $this->repository->getAll($superAdmin)->pluck('nip')->all()
        );
        $this->assertSame(
            ['199001012015011001'],
            $this->repository->getAll($admin)->pluck('nip')->all()
        );
        $this->assertSame(
            ['199001012015011001'],
            $this->repository->getAll($user)->pluck('nip')->all()
        );

        $userTanpaNip = new User(['role' => 'user', 'nip' => null]);
        try {
            $this->repository->getAll($userTanpaNip);
            $this->fail('Seharusnya melempar AuthorizationException');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('Akun pengguna belum terhubung dengan data pegawai.', $e->getMessage());
        }

        $guestUser = new User(['role' => 'guest']);
        $this->expectException(AuthorizationException::class);
        $this->repository->getAll($guestUser);
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

    public function test_employee_import_upserts_for_authorized_roles_and_keeps_admin_unit(): void
    {
        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        $admin = new User(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-A']);

        $created = $this->repository->updateOrCreate(
            ['nip' => '199001012015011010'],
            ['nama' => 'Pegawai Import', 'kode_unit_kerja' => 'UNIT-OTHER'],
            $admin
        );
        $updated = $this->repository->updateOrCreate(
            ['nip' => '199001012015011010'],
            ['nama' => 'Pegawai Diperbarui', 'kode_unit_kerja' => 'UNIT-OTHER'],
            $admin
        );
        $superAdminCreated = $this->repository->updateOrCreate(
            ['nip' => '199001012015011011'],
            ['nama' => 'Pegawai Super Admin', 'kode_unit_kerja' => null],
            new User(['role' => 'super_admin'])
        );

        $this->assertSame('UNIT-A', $created->kode_unit_kerja);
        $this->assertSame('UNIT-A', $updated->kode_unit_kerja);
        $this->assertSame('Pegawai Diperbarui', $updated->nama);
        $this->assertNull($superAdminCreated->kode_unit_kerja);
    }

    public function test_employee_repository_rejects_admin_without_unit_and_returns_user_master_data(): void
    {
        $adminWithoutUnit = new User(['role' => 'admin']);
        try {
            $this->repository->getAll($adminWithoutUnit);
            $this->fail('An admin without an assigned department must not query employee records.');
        } catch (AuthorizationException $exception) {
            $this->assertSame('Unit kerja Admin belum ditentukan.', $exception->getMessage());
        }

        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        Department::create(['kode' => 'UNIT-B', 'nama' => 'Unit B', 'status' => false]);
        $masterData = $this->repository->getMasterData(new User(['role' => 'user']));

        $this->assertSame(['UNIT-A'], $masterData['departments']->pluck('kode')->all());
        $this->assertCount(0, $masterData['districts']);
        $this->assertCount(0, $masterData['urbanVillages']);
    }

    public function test_rank_lookup_handles_missing_bounds_inactive_education_and_unknown_employee_type(): void
    {
        EducationLevel::create([
            'kode' => 'LIMITLESS-PPPK',
            'nama' => 'PPPK tanpa batas',
            'status' => true,
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 2,
            'golongan_pppk_minimum' => null,
            'golongan_pppk_maksimum' => null,
        ]);
        EducationLevel::create([
            'kode' => 'INACTIVE',
            'nama' => 'Pendidikan tidak aktif',
            'status' => false,
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 2,
        ]);
        EducationLevel::create([
            'kode' => 'SMA',
            'nama' => 'SMA',
            'status' => true,
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 2,
        ]);
        Rank::create(['kode' => 'I/a', 'nama' => 'Juru Muda', 'urutan_pangkat' => 1]);
        Rank::create(['kode' => 'I/b', 'nama' => 'Juru Muda Tk. I', 'urutan_pangkat' => 2]);
        Rank::create(['kode' => 'I/c', 'nama' => 'Juru', 'urutan_pangkat' => 3]);

        $this->assertCount(0, $this->repository->getRanksByEducation('LIMITLESS-PPPK', 'PPPK'));
        $this->assertCount(0, $this->repository->getRanksByEducation('INACTIVE', 'PNS'));
        $this->assertCount(0, $this->repository->getRanksByEducation('SMA', 'HONORER'));
        $this->assertSame(
            ['I/a', 'I/b'],
            $this->repository->getRanksByEducation('SMA', 'PNS')->pluck('kode')->all()
        );
    }
}

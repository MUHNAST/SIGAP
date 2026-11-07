<?php

namespace Tests\Feature;

use App\Imports\EmployeeImport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Pppk;
use App\Models\Rank;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use App\Services\EmployeeUserSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_imports_pns_and_normalizes_employee_fields(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        $import = new EmployeeImport(
            new EmployeeRepository(),
            new EmployeeUserSyncService()
        );
        $import->setUser($user);

        $import->collection(new Collection([
            new Collection([
                'nip' => '199001012020011001',
                'nama' => '  bUDI   santoso ',
                'email' => ' BUDI@EXAMPLE.COM ',
                'no_hp' => '+62 812-3456-7890',
                'status_pegawai' => 'pns',
                'pangkat_pns' => 'Penata Muda (III/a)',
                'tanggal_lahir' => '1 Januari 1990',
                'jenis_kelamin' => 'pria',
                'agama' => 'islam',
                'status_pernikahan' => 'kawin',
                'masa_kerja' => '5 tahun 3 bulan',
                'status' => 'non aktif',
            ]),
        ]));

        $employee = Employee::query()->findOrFail('199001012020011001');
        $this->assertSame('Budi   Santoso', $employee->nama);
        $this->assertSame('budi@example.com', $employee->email);
        $this->assertSame('081234567890', $employee->no_hp);
        $this->assertSame('PNS', $employee->status_pegawai);
        $this->assertNotNull($employee->id_pangkat);
        $this->assertSame('1990-01-01', $employee->tanggal_lahir->format('Y-m-d'));
        $this->assertSame('Laki-laki', $employee->jenis_kelamin);
        $this->assertSame('Islam', $employee->agama);
        $this->assertSame('Menikah', $employee->status_nikah);
        $this->assertSame(5, $employee->tahun_masa_kerja);
        $this->assertSame(3, $employee->bulan_masa_kerja);
        $this->assertSame('Nonaktif', $employee->status);
    }

    public function test_collection_imports_pppk_and_skips_invalid_or_duplicate_nip(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        Pppk::create([
            'kode' => 'IX',
            'nama' => 'Golongan IX',
            'urutan_golongan' => 9,
        ]);
        $import = new EmployeeImport(
            new EmployeeRepository(),
            new EmployeeUserSyncService()
        );
        $import->setUser($user);
        $rows = new Collection([
            new Collection(['nip' => '123', 'nama' => 'Invalid']),
            new Collection([
                'nip' => '199001012020011002',
                'nama' => 'Siti Aminah',
                'status_pegawai' => 'PPPK',
                'golongan_pppk' => 'Golongan IX (IX)',
            ]),
            new Collection([
                'nip' => '199001012020011002',
                'nama' => 'Duplicate',
                'status_pegawai' => 'PNS',
            ]),
        ]);

        $import->collection($rows);

        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseHas('employees', [
            'nip' => '199001012020011002',
            'status_pegawai' => 'PPPK',
            'kode_pppk' => 'IX',
            'nama' => 'Siti Aminah',
        ]);
    }

    public function test_collection_requires_a_user_with_import_permission(): void
    {
        $import = new EmployeeImport(
            new EmployeeRepository(),
            new EmployeeUserSyncService()
        );

        try {
            $import->collection(new Collection());
            $this->fail('Import without a user must be rejected.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(401, $exception->getStatusCode());
        }

        $import->setUser(User::factory()->create(['role' => 'user']));
        try {
            $import->collection(new Collection());
            $this->fail('A regular user must not import employees.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_admin_can_import_an_employee_for_their_own_department(): void
    {
        Department::create([
            'kode' => 'UNIT-ADMIN',
            'nama' => 'Unit Admin',
            'status' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-ADMIN',
        ]);
        $import = new EmployeeImport(
            new EmployeeRepository(),
            new EmployeeUserSyncService()
        );
        $import->setUser($user);

        $import->collection(new Collection([
            new Collection([
                'nip' => '199001012020011005',
                'nama' => 'Pegawai Admin',
                'unit_kerja' => 'Unit Admin',
                'status_pegawai' => 'PNS',
            ]),
        ]));

        $this->assertDatabaseHas('employees', [
            'nip' => '199001012020011005',
            'kode_unit_kerja' => 'UNIT-ADMIN',
        ]);
    }

    public function test_admin_cannot_import_an_employee_for_another_department(): void
    {
        Department::create([
            'kode' => 'UNIT-ADMIN',
            'nama' => 'Unit Admin',
            'status' => true,
        ]);
        Department::create([
            'kode' => 'UNIT-OTHER',
            'nama' => 'Unit Lain',
            'status' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-ADMIN',
        ]);
        $import = new EmployeeImport(
            new EmployeeRepository(),
            new EmployeeUserSyncService()
        );
        $import->setUser($user);

        $import->collection(new Collection([
            new Collection([
                'nip' => '199001012020011006',
                'nama' => 'Pegawai Unit Lain',
                'unit_kerja' => 'Unit Lain',
                'status_pegawai' => 'PNS',
            ]),
        ]));

        $this->assertDatabaseMissing('employees', [
            'nip' => '199001012020011006',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Pppk;
use App\Models\Province;
use App\Models\Rank;
use App\Models\RolePermission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    private function setupMasterData(): array
    {
        $deptA = Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit Kerja A', 'status' => true]);
        $deptB = Department::create(['kode' => 'UNIT-B', 'nama' => 'Unit Kerja B', 'status' => true]);

        $prov = Province::create(['id' => '33', 'nama' => 'Jawa Tengah']);
        $city = City::create(['id' => '3375', 'id_provinsi' => '33', 'nama' => 'Kota Pekalongan']);

        $edu = EducationLevel::create([
            'kode' => 'S1',
            'nama' => 'Sarjana',
            'status' => true,
            'pangkat_pns_minimum' => 9,
            'pangkat_pns_maksimum' => 12,
        ]);

        $rank = Rank::create(['kode' => 'III/a', 'nama' => 'Penata Muda', 'urutan_pangkat' => 9]);
        $pppkDefault = Pppk::create(['kode' => '0', 'nama' => 'Bukan PPPK', 'urutan_golongan' => 0]);
        $pos = Position::create(['kode' => 'POS-01', 'nama' => 'Analis Kepegawaian', 'usia_pensiun' => 58]);

        return compact('deptA', 'deptB', 'prov', 'city', 'edu', 'rank', 'pppkDefault', 'pos');
    }

    /**
     * Memeriksa Super Admin dapat melihat halaman index data pegawai.
     */
    public function test_super_admin_dapat_melihat_halaman_index_pegawai(): void
    {
        $this->setupMasterData();
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('employee.index'));

        $response->assertStatus(200);
        $response->assertViewIs('employee.index');
        $response->assertViewHas('data');
    }

    /**
     * Memeriksa Admin dengan permission dapat melihat halaman index pegawai.
     */
    public function test_admin_dengan_permission_dapat_melihat_index_pegawai(): void
    {
        $this->setupMasterData();
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);

        $perm = Permission::create(['kode' => 'pegawai.view', 'nama' => 'Lihat Pegawai', 'modul' => 'pegawai']);
        RolePermission::create(['role' => 'admin', 'permission_id' => $perm->id]);

        $response = $this->actingAs($admin)->get(route('employee.index'));

        $response->assertStatus(200);
        $response->assertViewIs('employee.index');
    }

    /**
     * Memeriksa Admin dilarang mengakses detail pegawai pada unit kerja yang berbeda (abort 403).
     */
    public function test_admin_dilarang_melihat_detail_pegawai_unit_lain(): void
    {
        $this->setupMasterData();
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);

        $perm = Permission::create(['kode' => 'pegawai.view', 'nama' => 'Lihat Pegawai', 'modul' => 'pegawai']);
        RolePermission::create(['role' => 'admin', 'permission_id' => $perm->id]);

        $employeeB = Employee::create([
            'nip' => '199505052020011002',
            'nama' => 'Pegawai Unit B',
            'kode_unit_kerja' => 'UNIT-B',
        ]);

        $response = $this->actingAs($admin)->get(route('employee.show', $employeeB->nip));

        // Pegawai unit lain tidak ditemukan dalam cakupan query Admin (ModelNotFoundException -> 404)
        $response->assertStatus(404);
    }

    /**
     * Memeriksa akun User dapat melihat detail kepegawaian pribadinya.
     */
    public function test_user_dapat_melihat_detail_pegawai_pribadi(): void
    {
        $this->setupMasterData();

        $employee = Employee::create([
            'nip' => '199202022018011003',
            'nama' => 'Pegawai Pribadi',
            'kode_unit_kerja' => 'UNIT-A',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $employee->nip,
            'kode_unit_kerja' => 'UNIT-A',
        ]);

        $response = $this->actingAs($user)->get(route('employe.detail'));

        $response->assertStatus(200);
        $response->assertViewIs('employee.show');
        $response->assertViewHas('data', function ($viewData) use ($employee) {
            return $viewData->nip === $employee->nip;
        });
    }

    /**
     * Memeriksa akun User tanpa NIP menghasilkan response 404 saat mengakses detail kepegawaian.
     */
    public function test_user_tanpa_nip_menghasilkan_404_saat_akses_detail(): void
    {
        $this->setupMasterData();
        $user = User::factory()->create([
            'role' => 'user',
            'nip' => null,
            'kode_unit_kerja' => 'UNIT-A',
        ]);

        $response = $this->actingAs($user)->get(route('employe.detail'));

        $response->assertStatus(404);
    }

    /**
     * Memeriksa validasi FormRequest: NIP kurang dari 18 digit gagal divalidasi.
     */
    public function test_store_pegawai_gagal_jika_format_nip_tidak_18_digit(): void
    {
        $this->setupMasterData();
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);

        $payload = [
            'nip' => '12345', // Hanya 5 digit
            'nama' => 'Pegawai Baru',
        ];

        $response = $this->actingAs($superAdmin)->post(route('employee.store'), $payload);

        $response->assertSessionHasErrors(['nip']);
    }

    /**
     * Memeriksa penyimpanan data pegawai baru berhasil dan memicu activity log.
     */
    public function test_store_pegawai_berhasil_saat_data_valid(): void
    {
        $master = $this->setupMasterData();
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);

        $payload = [
            'nip' => '199501012020011005',
            'nik' => '3375010101950005',
            'nama' => 'Ahmad Fauzi',
            'status_pegawai' => 'PNS',
            'id_pangkat' => $master['rank']->id,
            'tingkat_pendidikan' => $master['edu']->kode,
            'kode_unit_kerja' => $master['deptA']->kode,
            'kode_jabatan' => $master['pos']->kode,
            'tanggal_lahir' => '1995-01-01',
            'tanggal_masuk' => '2018-01-01',
            'tmt_pangkat_lama' => '2020-01-01',
            'tmt_kgb_lama' => '2022-01-01',
            'status' => 'Aktif',
        ];

        $response = $this->actingAs($superAdmin)->post(route('employee.store'), $payload);

        $response->assertRedirect(route('employee.index'));
        $response->assertSessionHas('success', 'Data pegawai berhasil ditambahkan.');

        $this->assertDatabaseHas('employees', [
            'nip' => '199501012020011005',
            'nama' => 'Ahmad Fauzi',
        ]);

        $saved = Employee::find('199501012020011005');
        $this->assertStringStartsWith('2024-01-01', (string) $saved->tmt_pangkat_baru);
        $this->assertStringStartsWith('2024-01-01', (string) $saved->tmt_kgb_baru);
    }

    public function test_super_admin_can_show_edit_and_update_employee(): void
    {
        $this->setupMasterData();
        $employee = Employee::create([
            'nip' => '199501012020011006',
            'nama' => 'Pegawai Lama',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get(route('employee.show', $employee->nip))
            ->assertOk()
            ->assertViewIs('employee.show');

        $this->actingAs($superAdmin)
            ->get(route('employee.edit', $employee->nip))
            ->assertOk()
            ->assertViewIs('employee.edit');

        $this->actingAs($superAdmin)
            ->put(route('employee.update', $employee->nip), [
                'nip' => $employee->nip,
                'nama' => 'Pegawai Diperbarui',
            ])
            ->assertRedirect(route('employee.index'))
            ->assertSessionHas('success', 'Data pegawai berhasil diperbarui.');

        $this->assertDatabaseHas('employees', [
            'nip' => $employee->nip,
            'nama' => 'Pegawai Diperbarui',
        ]);
    }

    public function test_reference_endpoints_return_employee_dropdown_data(): void
    {
        $master = $this->setupMasterData();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $this->get(route('employee.ranks-by-education', [
            'educationLevelCode' => $master['edu']->kode,
            'status_pegawai' => 'PNS',
        ]))->assertOk();

        $this->get(route('employee.cities', $master['prov']->id))->assertOk();
        $this->get(route('employee.districts', $master['city']->id))->assertOk();
        $this->get(route('employee.urban-villages', '3375001'))->assertOk();
    }

    public function test_template_endpoint_downloads_csv_template(): void
    {
        Excel::fake();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get(route('employee.template', 'csv'))
            ->assertOk();

        Excel::assertDownloaded('template_pegawai.csv');
    }

    public function test_export_rejects_unavailable_columns(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get(route('employee.export', 'csv') . '?columns[]=unknown')
            ->assertStatus(422);
    }

    public function test_import_rejects_an_unsupported_upload_type(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->post(route('employee.import'), [
                'file' => \Illuminate\Http\UploadedFile::fake()->create(
                    'pegawai.txt',
                    1,
                    'text/plain'
                ),
            ])
            ->assertSessionHasErrors('file');
    }
}

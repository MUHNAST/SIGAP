<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\City;
use App\Models\Department;
use App\Models\District;
use App\Models\EducationLevel;
use App\Models\Position;
use App\Models\Province;
use App\Models\Rank;
use App\Models\UrbanVillage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSelfUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_update_form_request_rejects_missing_required_employee_data(): void
    {
        $employee = Employee::create([
            'nip' => '199001012020011020',
            'nama' => 'Pegawai Pengguna',
            'status_pegawai' => 'PNS',
        ]);
        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $employee->nip,
        ]);

        $this->actingAs($user)
            ->from(route('setting.index'))
            ->put(route('setting.employee.update'), [])
            ->assertRedirect(route('setting.index'))
            ->assertSessionHasErrorsIn('employee', [
                'nik',
                'nama',
                'email',
                'no_hp',
                'status_pegawai',
                'tanggal_masuk',
            ]);
    }

    public function test_user_can_update_their_employee_details_with_valid_data(): void
    {
        Department::create(['kode' => 'UNIT-SELF', 'nama' => 'Unit Self', 'status' => true]);
        Province::create(['id' => '33', 'nama' => 'Jawa Tengah']);
        City::create(['id' => '3375', 'id_provinsi' => '33', 'nama' => 'Pekalongan']);
        District::create(['id' => '3375010', 'id_kota' => '3375', 'nama' => 'Pekalongan Timur']);
        UrbanVillage::create(['id' => '3375010001', 'id_kecamatan' => '3375010', 'nama' => 'Setono']);
        EducationLevel::create([
            'kode' => 'S1',
            'nama' => 'Sarjana',
            'status' => true,
            'pangkat_pns_minimum' => 9,
            'pangkat_pns_maksimum' => 12,
        ]);
        $rank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        Position::create([
            'kode' => 'POS-SELF',
            'nama' => 'Analis',
            'usia_pensiun' => 58,
        ]);
        $employee = Employee::create([
            'nip' => '199001012020011021',
            'nama' => 'Nama Sebelum',
            'status_pegawai' => 'PNS',
            'kode_unit_kerja' => 'UNIT-SELF',
            'kode_jabatan' => 'POS-SELF',
            'tingkat_pendidikan' => 'S1',
            'id_pangkat' => $rank->id,
        ]);
        $user = User::factory()->create([
            'role' => 'user',
            'nip' => $employee->nip,
            'kode_unit_kerja' => 'UNIT-SELF',
        ]);

        $this->actingAs($user)
            ->from(route('setting.index'))
            ->put(route('setting.employee.update'), [
                'nik' => '3375010101900001',
                'nama' => 'Nama Sesudah',
                'email' => 'pegawai@example.test',
                'no_hp' => '+62 812-3456-7890',
                'tempat_lahir' => '3375',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'Laki-laki',
                'agama' => 'Islam',
                'status_nikah' => 'Menikah',
                'tingkat_pendidikan' => 'S1',
                'alamat' => 'Jalan Utama',
                'id_provinsi' => '33',
                'id_kota' => '3375',
                'id_kecamatan' => '3375010',
                'id_kelurahan' => '3375010001',
                'status_pegawai' => 'PNS',
                'tanggal_masuk' => '2010-01-01',
                'kode_unit_kerja' => 'UNIT-SELF',
                'kode_jabatan' => 'POS-SELF',
                'id_pangkat' => $rank->id,
                'tmt_pangkat_lama' => '2020-01-01',
                'tmt_kgb_lama' => '2022-01-01',
                'tahun_masa_kerja' => 14,
                'bulan_masa_kerja' => 0,
            ])
            ->assertRedirect(route('setting.index'))
            ->assertSessionHas('success', 'Data pegawai berhasil diperbarui.');

        $this->assertDatabaseHas('employees', [
            'nip' => $employee->nip,
            'nama' => 'Nama Sesudah',
            'no_hp' => '081234567890',
        ]);
    }
}

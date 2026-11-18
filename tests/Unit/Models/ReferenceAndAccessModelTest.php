<?php

namespace Tests\Unit\Models;

use App\Models\City;
use App\Models\District;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\LoginDevice;
use App\Models\Permission;
use App\Models\Province;
use App\Models\RolePermission;
use App\Models\UrbanVillage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceAndAccessModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_geographic_models_resolve_parent_and_child_relations(): void
    {
        $province = Province::create(['id' => '01', 'nama' => 'Provinsi Uji']);
        $city = City::create([
            'id' => '0101',
            'id_provinsi' => $province->id,
            'nama' => 'Kota Uji',
        ]);
        $district = District::create([
            'id' => '0101001',
            'id_kota' => $city->id,
            'nama' => 'Kecamatan Uji',
        ]);
        $village = UrbanVillage::create([
            'id' => '0101001001',
            'id_kecamatan' => $district->id,
            'nama' => 'Kelurahan Uji',
        ]);

        $this->assertSame($province->id, $city->province->id);
        $this->assertSame($city->id, $district->city->id);
        $this->assertSame($district->id, $village->district->id);
        $this->assertSame($city->id, $province->cities->sole()->id);
        $this->assertSame($district->id, $city->districts->sole()->id);
        $this->assertSame($village->id, $district->urbanVillages->sole()->id);
        $this->assertFalse($province->getIncrementing());
        $this->assertSame('string', $city->getKeyType());
        $this->assertSame('id', $village->getKeyName());
    }

    public function test_education_level_casts_fields_deletes_and_relates_employees(): void
    {
        $level = EducationLevel::create([
            'kode' => 'S1',
            'nama' => 'Sarjana',
            'pangkat_pns_minimum' => '3',
            'pangkat_pns_maksimum' => '7',
            'golongan_pppk_minimum' => null,
            'golongan_pppk_maksimum' => '6',
            'status' => 1,
        ]);
        $employee = Employee::create([
            'nip' => '198501012010011001',
            'nama' => 'Pegawai Uji',
            'status_pegawai' => 'PNS',
            'tingkat_pendidikan' => $level->kode,
        ]);

        $this->assertSame(3, $level->fresh()->pangkat_pns_minimum);
        $this->assertSame(7, $level->fresh()->pangkat_pns_maksimum);
        $this->assertSame(6, $level->fresh()->golongan_pppk_maksimum);
        $this->assertTrue($level->fresh()->status);
        $this->assertSame($employee->nip, $level->employees->sole()->nip);
        $this->assertSame($level->kode, $employee->educationLevel->kode);

        $this->assertTrue($level->delete());

        $this->assertDatabaseMissing('education_levels', ['kode' => 'S1']);
        $this->assertNull(EducationLevel::find('S1'));
    }

    public function test_login_device_casts_last_activity_and_resolves_user_relations(): void
    {
        $user = User::factory()->create();
        $device = LoginDevice::create([
            'id_user' => $user->id,
            'device_fingerprint' => 'fingerprint-1',
            'nama_perangkat' => 'Browser uji',
            'aktivitas_terakhir' => '2026-11-14 10:00:00',
        ]);

        $this->assertSame($user->id, $device->user->id);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $device->fresh()->aktivitas_terakhir);
        $this->assertSame('Browser uji', $device->fresh()->nama_perangkat);
    }

    public function test_permission_and_role_permission_relations_use_the_permission_key(): void
    {
        $permission = Permission::create([
            'nama' => 'Lihat laporan',
            'kode' => 'report.view',
            'modul' => 'report',
        ]);
        $rolePermission = RolePermission::create([
            'role' => 'admin',
            'permission_id' => $permission->id,
        ]);

        $this->assertSame($permission->id, $rolePermission->permission->id);
        $this->assertSame($rolePermission->id, $permission->rolePermissions->sole()->id);
        $this->assertDatabaseHas('role_permissions', [
            'role' => 'admin',
            'permission_id' => $permission->id,
        ]);
    }
}

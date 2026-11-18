<?php

namespace Tests\Unit\Models;

use App\Models\City;
use App\Models\Department;
use App\Models\District;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\KgbHistory;
use App\Models\LoginDevice;
use App\Models\Notification;
use App\Models\Position;
use App\Models\Pppk;
use App\Models\PromotionHistory;
use App\Models\Province;
use App\Models\Rank;
use App\Models\RolePermission;
use App\Models\Salary;
use App\Models\UrbanVillage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeRelationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_relations_casts_and_latest_history_resolve_by_business_keys(): void
    {
        $department = Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        $position = Position::create(['kode' => 'POS-A', 'nama' => 'Jabatan A', 'usia_pensiun' => 58]);
        $rank = Rank::create(['kode' => 'III/a', 'nama' => 'Penata Muda', 'urutan_pangkat' => 9]);
        $pppk = Pppk::create(['kode' => 'IX', 'nama' => 'Golongan IX', 'urutan_golongan' => '9']);
        $education = EducationLevel::create([
            'kode' => 'S1',
            'nama' => 'Sarjana',
            'pangkat_pns_minimum' => 1,
            'pangkat_pns_maksimum' => 9,
            'status' => 1,
        ]);
        $province = Province::create(['id' => '01', 'nama' => 'Provinsi']);
        $city = City::create(['id' => '0101', 'id_provinsi' => '01', 'nama' => 'Kota']);
        $district = District::create(['id' => '0101001', 'id_kota' => '0101', 'nama' => 'Kecamatan']);
        $village = UrbanVillage::create(['id' => '0101001001', 'id_kecamatan' => '0101001', 'nama' => 'Kelurahan']);
        $nip = '198501012010011001';
        $employee = Employee::create([
            'nip' => $nip,
            'nama' => 'Pegawai Uji',
            'status_pegawai' => 'PNS',
            'kode_unit_kerja' => $department->kode,
            'kode_jabatan' => $position->kode,
            'id_pangkat' => $rank->id,
            'kode_pppk' => $pppk->kode,
            'tingkat_pendidikan' => $education->kode,
            'id_provinsi' => $province->id,
            'id_kota' => $city->id,
            'id_kecamatan' => $district->id,
            'id_kelurahan' => $village->id,
            'tempat_lahir' => $city->id,
            'tanggal_lahir' => '1985-01-01',
            'tanggal_masuk' => '2010-01-01',
            'tahun_masa_kerja' => '15',
            'bulan_masa_kerja' => '10',
        ]);
        $user = User::factory()->create(['nip' => $nip]);
        $olderHistory = KgbHistory::create([
            'nip' => $nip,
            'id_pangkat' => $rank->id,
            'id_user' => $user->id,
            'tanggal_sk_baru' => '2024-01-01',
            'tmt_kgb_baru' => '2024-02-01',
            'gaji_pokok_baru' => '5000000',
        ]);
        $latestHistory = KgbHistory::create([
            'nip' => $nip,
            'id_pangkat' => $rank->id,
            'id_user' => $user->id,
            'tanggal_sk_baru' => '2026-01-01',
            'tmt_kgb_baru' => '2026-02-01',
            'gaji_pokok_baru' => '5500000',
        ]);

        $this->assertSame($nip, $employee->getRouteKey());
        $this->assertSame('string', $employee->getKeyType());
        $this->assertSame($user->id, $employee->user->id);
        $this->assertSame($position->kode, $employee->position->kode);
        $this->assertSame($rank->id, $employee->rank->id);
        $this->assertSame($pppk->kode, $employee->pppk->kode);
        $this->assertSame($education->kode, $employee->educationLevel->kode);
        $this->assertSame($department->kode, $employee->department->kode);
        $this->assertSame($province->id, $employee->province->id);
        $this->assertSame($city->id, $employee->city->id);
        $this->assertSame($district->id, $employee->district->id);
        $this->assertSame($village->id, $employee->urbanVillage->id);
        $this->assertSame($city->id, $employee->birthplace->id);
        $this->assertSame($latestHistory->id, $employee->latestKgbHistory->id);
        $this->assertEqualsCanonicalizing(
            [$olderHistory->id, $latestHistory->id],
            $employee->kgbHistories->pluck('id')->all()
        );
        $this->assertSame(15, $employee->fresh()->tahun_masa_kerja);
        $this->assertSame(10, $employee->fresh()->bulan_masa_kerja);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $employee->fresh()->tanggal_lahir);
        $this->assertSame(9, $pppk->fresh()->urutan_golongan);
        $this->assertSame(5500000, $latestHistory->fresh()->gaji_pokok_baru);
        $this->assertSame($employee->nip, $latestHistory->employee->nip);
        $this->assertSame($user->id, $latestHistory->user->id);
        $this->assertSame($rank->id, $latestHistory->rank->id);
    }

    public function test_employee_documents_notifications_and_rank_history_relations_resolve(): void
    {
        $rank = Rank::create(['kode' => 'IV/a', 'nama' => 'Pembina', 'urutan_pangkat' => 13]);
        $employee = Employee::create([
            'nip' => '198501012010011002',
            'nama' => 'Pegawai Uji',
            'status_pegawai' => 'PNS',
            'id_pangkat' => $rank->id,
        ]);
        $user = User::factory()->create(['nip' => $employee->nip]);
        $document = EmployeeDocument::create([
            'nip' => $employee->nip,
            'id_user' => $user->id,
            'jenis_dokumen' => 'SK',
            'nama_file' => 'sk.pdf',
            'nama_file_asli' => 'sk-asli.pdf',
        ]);
        $notification = Notification::create([
            'nip' => $employee->nip,
            'jenis' => 'KGB',
            'judul' => 'Pengingat KGB',
            'pesan' => 'Pesan pengingat',
            'tanggal_notifikasi' => today()->toDateString(),
            'tanggal_referensi' => today()->addMonth()->toDateString(),
            'sudah_dibaca' => 1,
        ]);
        $salary = Salary::create([
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 10,
            'gaji_pokok' => 6000000,
        ]);
        $history = KgbHistory::create([
            'nip' => $employee->nip,
            'id_pangkat' => $rank->id,
            'tanggal_sk_baru' => today()->toDateString(),
            'tmt_kgb_baru' => today()->addMonth()->toDateString(),
        ]);
        $promotion = PromotionHistory::create([
            'nip' => $employee->nip,
            'id_pangkat_lama' => $rank->id,
            'id_pangkat_baru' => $rank->id,
            'tmt_pangkat' => today()->toDateString(),
        ]);

        $this->assertSame($employee->nip, $document->employee->nip);
        $this->assertSame($user->id, $document->user->id);
        $this->assertSame($employee->nip, $notification->employee->nip);
        $this->assertTrue($notification->fresh()->sudah_dibaca);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $notification->fresh()->tanggal_referensi);
        $this->assertSame($rank->id, $salary->rank->id);
        $this->assertSame([$salary->id], $rank->salaries->pluck('id')->all());
        $this->assertSame([$history->id], $rank->kgbHistories->pluck('id')->all());
        $this->assertSame([$employee->nip], $rank->employees->pluck('nip')->all());
        $this->assertSame([$promotion->id], $rank->oldPromotionHistories->pluck('id')->all());
        $this->assertSame([$promotion->id], $rank->newPromotionHistories->pluck('id')->all());
        $this->assertSame($rank->id, $promotion->oldRank->id);
        $this->assertSame($rank->id, $promotion->newRank->id);
        $this->assertSame($employee->nip, $promotion->employee->nip);
    }

    public function test_users_grant_super_admin_permissions_and_require_role_permission_records(): void
    {
        $permission = \App\Models\Permission::create([
            'nama' => 'Lihat pegawai',
            'kode' => 'pegawai.view',
            'modul' => 'pegawai',
        ]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $permission->id]);
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($admin->hasPermission('pegawai.view'));
        $this->assertFalse($admin->hasPermission('pegawai.delete'));
        $this->assertFalse($user->hasPermission('pegawai.view'));
        $this->assertTrue($superAdmin->hasPermission('unregistered.permission'));
        $this->assertSame($permission->id, $permission->rolePermissions->sole()->permission->id);
    }

    public function test_user_login_devices_relation_uses_the_migration_foreign_key(): void
    {
        $user = User::factory()->create();
        $device = LoginDevice::create([
            'id_user' => $user->id,
            'device_fingerprint' => 'fingerprint-001',
            'nama_perangkat' => 'Laptop',
            'session_id' => 'session-001',
        ]);

        $this->assertSame(
            [$device->id],
            $user->loginDevices->pluck('id')->all()
        );
        $this->assertSame($user->id, $device->user->id);
    }
}

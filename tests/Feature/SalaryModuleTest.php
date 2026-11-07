<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Rank;
use App\Models\RolePermission;
use App\Models\Salary;
use App\Models\User;
use App\Repositories\SalaryRepository;
use App\Services\SalaryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalaryModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_list_create_and_update_salary_for_a_pns_rank(): void
    {
        $rank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($user)->get(route('salary.index'))
            ->assertOk()
            ->assertViewIs('salary.index');

        $this->post(route('salary.store'), [
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 0,
            'gaji_pokok' => 2785700,
        ])->assertRedirect(route('salary.index'));

        $salary = Salary::firstOrFail();
        $this->put(route('salary.update', $salary->id), [
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 0,
            'gaji_pokok' => 2785700,
        ])->assertRedirect(route('salary.index'));

        $this->put(route('salary.update', $salary->id), [
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 32,
            'gaji_pokok' => 4575200,
        ])->assertRedirect(route('salary.index'));

        $this->assertDatabaseHas('salaries', [
            'id' => $salary->id,
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 32,
            'gaji_pokok' => 4575200,
        ]);
    }

    public function test_salary_validation_rejects_duplicate_invalid_and_missing_rank_values(): void
    {
        $rank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        Salary::create([
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 2,
            'gaji_pokok' => 2873500,
        ]);
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($user)->post(route('salary.store'), [
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 2,
            'gaji_pokok' => 2964000,
        ])->assertSessionHasErrors('tahun_masa_kerja');

        $this->post(route('salary.store'), [
            'id_pangkat' => 99999,
            'tahun_masa_kerja' => 34,
            'gaji_pokok' => 100000000,
        ])->assertSessionHasErrors(['id_pangkat', 'tahun_masa_kerja'])
            ->assertSessionDoesntHaveErrors('gaji_pokok');

        $this->post(route('salary.store'), [
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 33,
            'gaji_pokok' => 100000001,
        ])->assertSessionHasErrors('gaji_pokok');

        $this->post(route('salary.store'), [
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => -1,
            'gaji_pokok' => -1,
        ])->assertSessionHasErrors(['tahun_masa_kerja', 'gaji_pokok']);

        $this->post(route('salary.store'), [])
            ->assertSessionHasErrors(['id_pangkat', 'tahun_masa_kerja', 'gaji_pokok']);
    }

    public function test_salary_request_normalizes_grouped_amounts(): void
    {
        $rank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($user)->post(route('salary.store'), [
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => '4',
            'gaji_pokok' => '2.964.000',
        ])->assertRedirect(route('salary.index'));

        $this->assertDatabaseHas('salaries', [
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 4,
            'gaji_pokok' => 2964000,
        ]);
    }

    public function test_salary_service_and_repository_lookup_existing_and_missing_records(): void
    {
        $rank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        $salary = Salary::create([
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 4,
            'gaji_pokok' => 2964000,
        ]);
        $service = new SalaryService(new SalaryRepository());

        $this->assertCount(1, $service->getAll());
        $this->assertSame($salary->id, $service->getById($salary->id)->id);
        $this->expectException(ModelNotFoundException::class);
        $service->getById(99999);
    }

    public function test_salary_unique_rule_is_per_rank_and_index_permission_is_enforced(): void
    {
        $firstRank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        $secondRank = Rank::create([
            'kode' => 'III/b',
            'nama' => 'Penata Muda Tingkat I',
            'urutan_pangkat' => 10,
        ]);
        Salary::create([
            'id_pangkat' => $firstRank->id,
            'tahun_masa_kerja' => 2,
            'gaji_pokok' => 2964000,
        ]);

        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        $permission = Permission::create([
            'kode' => 'gaji.view',
            'nama' => 'Lihat Gaji',
            'modul' => 'gaji',
        ]);
        $createPermission = Permission::create([
            'kode' => 'gaji.create',
            'nama' => 'Tambah Gaji',
            'modul' => 'gaji',
        ]);
        $updatePermission = Permission::create([
            'kode' => 'gaji.update',
            'nama' => 'Ubah Gaji',
            'modul' => 'gaji',
        ]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $permission->id]);
        RolePermission::create([
            'role' => 'admin',
            'permission_id' => $createPermission->id,
        ]);
        RolePermission::create([
            'role' => 'admin',
            'permission_id' => $updatePermission->id,
        ]);

        $authorized = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        $this->actingAs($authorized)->get(route('salary.index'))->assertOk();

        $this->post(route('salary.store'), [
            'id_pangkat' => $secondRank->id,
            'tahun_masa_kerja' => 2,
            'gaji_pokok' => 2995000,
        ])->assertRedirect(route('salary.index'));
        $salary = Salary::where('id_pangkat', $secondRank->id)->firstOrFail();
        $this->put(route('salary.update', $salary->id), [
            'id_pangkat' => $secondRank->id,
            'tahun_masa_kerja' => 2,
            'gaji_pokok' => 2995000,
        ])->assertRedirect(route('salary.index'));

        RolePermission::query()->delete();
        $unauthorized = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        $this->actingAs($unauthorized)->get(route('salary.index'))->assertForbidden();
        $this->post(route('salary.store'), [
            'id_pangkat' => $firstRank->id,
            'tahun_masa_kerja' => 6,
            'gaji_pokok' => 3057300,
        ])->assertForbidden();
        $this->put(route('salary.update', $salary->id), [
            'id_pangkat' => $secondRank->id,
            'tahun_masa_kerja' => 2,
            'gaji_pokok' => 2995000,
        ])->assertForbidden();
    }

    public function test_salary_activity_log_records_the_actual_service_year_field(): void
    {
        $rank = Rank::create([
            'kode' => 'III/a',
            'nama' => 'Penata Muda',
            'urutan_pangkat' => 9,
        ]);
        $user = User::factory()->create([
            'role' => 'super_admin',
            'nama' => 'Super Admin Uji',
        ]);

        $this->actingAs($user)->post(route('salary.store'), [
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 4,
            'gaji_pokok' => 2964000,
        ])->assertRedirect(route('salary.index'));

        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $user->id,
            'aktivitas' => 'Tambah Data Gaji',
            'deskripsi' => ''.$user->nama.' menambahkan data gaji Penata Muda masa kerja 4 tahun.',
        ]);

        $salary = Salary::where('id_pangkat', $rank->id)->firstOrFail();
        $this->put(route('salary.update', $salary->id), [
            'id_pangkat' => $rank->id,
            'tahun_masa_kerja' => 5,
            'gaji_pokok' => 3057300,
        ])->assertRedirect(route('salary.index'));

        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $user->id,
            'aktivitas' => 'Update Data Gaji',
            'deskripsi' => ''.$user->nama.' mengubah data gaji Penata Muda masa kerja 5 tahun.',
        ]);
    }
}

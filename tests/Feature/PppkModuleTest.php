<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Pppk;
use App\Models\RolePermission;
use App\Models\User;
use App\Repositories\PppkRepository;
use App\Services\PppkService;
use Database\Seeders\PppkSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PppkModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_list_create_and_update_pppk_grades(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($user)->get(route('pppk.index'))
            ->assertOk()
            ->assertViewIs('pppk.index');

        $this->post(route('pppk.store'), [
            'kode' => 'III',
            'nama' => 'Golongan III',
            'urutan_golongan' => 3,
        ])->assertRedirect(route('pppk.index'));

        $this->put(route('pppk.update', 'III'), [
            'kode' => 'III',
            'nama' => 'Golongan III',
            'urutan_golongan' => 3,
        ])->assertRedirect(route('pppk.index'));

        $this->put(route('pppk.update', 'III'), [
            'kode' => 'IV',
            'nama' => 'Golongan IV',
            'urutan_golongan' => 4,
        ])->assertRedirect(route('pppk.index'));

        $this->assertDatabaseHas('pppk', [
            'kode' => 'IV',
            'nama' => 'Golongan IV',
            'urutan_golongan' => 4,
        ]);
    }

    public function test_pppk_validation_rejects_duplicate_values_and_invalid_order(): void
    {
        $this->seed(PppkSeeder::class);
        $user = User::factory()->create(['role' => 'super_admin']);
        $count = Pppk::count();

        $this->actingAs($user)->post(route('pppk.store'), [
            'kode' => 'I',
            'nama' => 'Golongan Lain',
            'urutan_golongan' => 18,
        ])->assertSessionHasErrors('kode');

        $this->post(route('pppk.store'), [
            'kode' => 'I',
            'nama' => 'Golongan Baru',
            'urutan_golongan' => 1,
        ])->assertSessionHasErrors(['kode', 'urutan_golongan']);

        $this->post(route('pppk.store'), [
            'kode' => 'II',
            'nama' => 'Golongan Tidak Valid',
            'urutan_golongan' => 0,
        ])->assertSessionHasErrors('urutan_golongan');

        $this->assertDatabaseCount('pppk', $count);
    }

    public function test_pppk_index_permission_allows_authorized_admin_and_denies_unauthorized_admin(): void
    {
        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        $permission = Permission::create([
            'kode' => 'pppk.view',
            'nama' => 'Lihat Golongan PPPK',
            'modul' => 'pppk',
        ]);
        $createPermission = Permission::create([
            'kode' => 'pppk.create',
            'nama' => 'Tambah Golongan PPPK',
            'modul' => 'pppk',
        ]);
        $updatePermission = Permission::create([
            'kode' => 'pppk.update',
            'nama' => 'Ubah Golongan PPPK',
            'modul' => 'pppk',
        ]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $permission->id]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $createPermission->id]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $updatePermission->id]);
        $existingPppk = Pppk::create([
            'kode' => 'III',
            'nama' => 'Golongan III',
            'urutan_golongan' => 3,
        ]);

        $authorized = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        $this->actingAs($authorized)->get(route('pppk.index'))->assertOk();
        $this->post(route('pppk.store'), [
            'kode' => 'IV',
            'nama' => 'Golongan IV',
            'urutan_golongan' => 4,
        ])->assertRedirect(route('pppk.index'));
        $this->put(route('pppk.update', $existingPppk), [
            'kode' => 'III',
            'nama' => 'Golongan III',
            'urutan_golongan' => 3,
        ])->assertRedirect(route('pppk.index'));

        RolePermission::query()->delete();
        $unauthorized = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        $this->actingAs($unauthorized)->get(route('pppk.index'))->assertForbidden();
        $this->post(route('pppk.store'), [
            'kode' => 'V',
            'nama' => 'Golongan V',
            'urutan_golongan' => 5,
        ])->assertForbidden();
        $this->put(route('pppk.update', $existingPppk), [
            'kode' => 'III',
            'nama' => 'Golongan III',
            'urutan_golongan' => 3,
        ])->assertForbidden();
    }

    public function test_pppk_service_lookup_returns_existing_record_and_throws_for_missing_code(): void
    {
        $pppk = Pppk::create([
            'kode' => 'XIII',
            'nama' => 'Golongan XIII',
            'urutan_golongan' => 13,
        ]);
        $service = new PppkService(new PppkRepository());

        $this->assertSame($pppk->kode, $service->getById('XIII')->kode);
        $this->expectException(ModelNotFoundException::class);
        $service->getById('NOT-FOUND');
    }
}

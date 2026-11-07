<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Position;
use App\Models\RolePermission;
use App\Models\User;
use App\Repositories\PositionRepository;
use App\Services\PositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class PositionModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_list_create_and_update_positions_at_age_limits(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        Position::create(['kode' => 'POS-OLD', 'nama' => 'Jabatan Lama', 'usia_pensiun' => 60]);

        $this->actingAs($user)->get(route('position.index'))
            ->assertOk()
            ->assertViewIs('position.index');

        $this->post(route('position.store'), [
            'kode' => 'POS-58',
            'nama' => 'Jabatan Batas Bawah',
            'usia_pensiun' => 58,
        ])->assertRedirect(route('position.index'));

        $this->put(route('position.update', 'POS-58'), [
            'kode' => 'POS-58',
            'nama' => 'Jabatan Batas Bawah',
            'usia_pensiun' => 58,
        ])->assertRedirect(route('position.index'));

        $this->put(route('position.update', 'POS-58'), [
            'kode' => 'POS-58',
            'nama' => 'Jabatan Batas Atas',
            'usia_pensiun' => 70,
        ])->assertRedirect(route('position.index'));

        $this->assertDatabaseHas('positions', [
            'kode' => 'POS-58',
            'nama' => 'Jabatan Batas Atas',
            'usia_pensiun' => 70,
        ]);
    }

    public function test_position_validation_rejects_duplicate_values_and_age_outside_limits(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        Position::create(['kode' => 'POS-01', 'nama' => 'Analis', 'usia_pensiun' => 58]);

        $this->actingAs($user)->post(route('position.store'), [
            'kode' => 'POS-01',
            'nama' => 'Jabatan Baru',
            'usia_pensiun' => 57,
        ])->assertSessionHasErrors(['kode', 'usia_pensiun']);

        $this->post(route('position.store'), [
            'kode' => 'POS-02',
            'nama' => 'Analis',
            'usia_pensiun' => 71,
        ])->assertSessionHasErrors(['nama', 'usia_pensiun']);

        $this->assertDatabaseCount('positions', 1);
    }

    public function test_position_index_permission_allows_authorized_admin_and_denies_unauthorized_admin(): void
    {
        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        $permission = Permission::create([
            'kode' => 'jabatan.view',
            'nama' => 'Lihat Jabatan',
            'modul' => 'jabatan',
        ]);
        $createPermission = Permission::create([
            'kode' => 'jabatan.create',
            'nama' => 'Tambah Jabatan',
            'modul' => 'jabatan',
        ]);
        $updatePermission = Permission::create([
            'kode' => 'jabatan.update',
            'nama' => 'Ubah Jabatan',
            'modul' => 'jabatan',
        ]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $permission->id]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $createPermission->id]);
        RolePermission::create(['role' => 'admin', 'permission_id' => $updatePermission->id]);
        $existingPosition = Position::create([
            'kode' => 'POS-EXIST',
            'nama' => 'Jabatan Existing',
            'usia_pensiun' => 58,
        ]);

        $authorized = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        $this->actingAs($authorized)->get(route('position.index'))->assertOk();
        $this->post(route('position.store'), [
            'kode' => 'POS-ADMIN',
            'nama' => 'Jabatan Admin',
            'usia_pensiun' => 58,
        ])->assertRedirect(route('position.index'));
        $this->put(route('position.update', $existingPosition), [
            'kode' => 'POS-EXIST',
            'nama' => 'Jabatan Existing',
            'usia_pensiun' => 58,
        ])->assertRedirect(route('position.index'));

        RolePermission::query()->delete();
        $unauthorized = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        $this->actingAs($unauthorized)->get(route('position.index'))->assertForbidden();
        $this->post(route('position.store'), [
            'kode' => 'POS-DENIED',
            'nama' => 'Jabatan Ditolak',
            'usia_pensiun' => 58,
        ])->assertForbidden();
        $this->put(route('position.update', $existingPosition), [
            'kode' => 'POS-EXIST',
            'nama' => 'Jabatan Ditolak',
            'usia_pensiun' => 58,
        ])->assertForbidden();
    }

    public function test_position_service_lookup_returns_existing_record_and_throws_for_missing_code(): void
    {
        $position = Position::create([
            'kode' => 'POS-03',
            'nama' => 'Pengelola',
            'usia_pensiun' => 58,
        ]);
        $service = new PositionService(new PositionRepository());

        $this->assertSame($position->kode, $service->getByCode('POS-03')->kode);
        $this->expectException(ModelNotFoundException::class);
        $service->getByCode('NOT-FOUND');
    }
}

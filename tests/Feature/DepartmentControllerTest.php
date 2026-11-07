<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_department_index(): void
    {
        Department::create([
            'kode' => 'UNIT-A',
            'nama' => 'Unit A',
            'status' => true,
        ]);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get(route('department.index'))
            ->assertOk()
            ->assertViewIs('department.index')
            ->assertViewHas('data', fn ($data) => $data->contains('kode', 'UNIT-A'));
    }

    public function test_admin_without_permission_is_denied(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('department.index'))
            ->assertForbidden();
    }

    public function test_admin_with_view_permission_can_access_department_index(): void
    {
        Department::create(['kode' => 'UNIT-A', 'nama' => 'Unit A', 'status' => true]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-A',
        ]);
        $permission = Permission::create([
            'kode' => 'unit_kerja.view',
            'nama' => 'Lihat Unit Kerja',
            'modul' => 'unit_kerja',
        ]);
        RolePermission::create([
            'role' => 'admin',
            'permission_id' => $permission->id,
        ]);
        $this->assertTrue($admin->hasPermission('unit_kerja.view'));

        $this->actingAs($admin)
            ->get(route('department.index'))
            ->assertOk();
    }

    public function test_non_admin_role_cannot_access_department_routes(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('department.index'))
            ->assertForbidden();
    }

    public function test_store_validates_and_normalizes_department_data(): void
    {
        Department::create(['kode' => 'UNIT-ADMIN', 'nama' => 'Unit Admin', 'status' => true]);
        $permission = Permission::create([
            'kode' => 'unit_kerja.create',
            'nama' => 'Tambah Unit Kerja',
            'modul' => 'unit_kerja',
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-ADMIN',
        ]);
        RolePermission::create([
            'role' => 'admin',
            'permission_id' => $permission->id,
        ]);

        $this->actingAs($admin)
            ->post(route('department.store'), [
                'kode' => 'UNIT-01',
                'nama' => '  kANTOR   pUSAT ',
                'deskripsi' => '  Kantor   administrasi  ',
                'status' => '1',
            ])
            ->assertRedirect(route('department.index'))
            ->assertSessionHas('success', 'Data unit kerja berhasil ditambahkan');

        $this->assertDatabaseHas('departments', [
            'kode' => 'UNIT-01',
            'nama' => 'Kantor Pusat',
            'deskripsi' => 'Kantor administrasi',
            'status' => 1,
        ]);
    }

    public function test_store_rejects_missing_fields_duplicate_values_and_invalid_status(): void
    {
        Department::create([
            'kode' => 'UNIT-01',
            'nama' => 'Kantor Pusat',
            'status' => true,
        ]);
        $permission = Permission::create([
            'kode' => 'unit_kerja.create',
            'nama' => 'Tambah Unit Kerja',
            'modul' => 'unit_kerja',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        RolePermission::create([
            'role' => 'admin',
            'permission_id' => $permission->id,
        ]);

        $this->actingAs($admin)
            ->from(route('department.index'))
            ->post(route('department.store'), [])
            ->assertSessionHasErrors(['kode', 'nama', 'status']);

        $this->actingAs($admin)
            ->from(route('department.index'))
            ->post(route('department.store'), [
                'kode' => 'UNIT-01',
                'nama' => 'Kantor Pusat',
                'status' => 'not-a-boolean',
            ])
            ->assertSessionHasErrors(['kode', 'nama', 'status']);
    }

    public function test_update_allows_current_unique_values_and_rejects_another_department_values(): void
    {
        $first = Department::create([
            'kode' => 'UNIT-01',
            'nama' => 'Kantor Pusat',
            'status' => true,
        ]);
        Department::create([
            'kode' => 'UNIT-02',
            'nama' => 'Kantor Cabang',
            'status' => true,
        ]);
        Department::create([
            'kode' => 'UNIT-ADMIN',
            'nama' => 'Unit Admin',
            'status' => true,
        ]);
        $permission = Permission::create([
            'kode' => 'unit_kerja.update',
            'nama' => 'Ubah Unit Kerja',
            'modul' => 'unit_kerja',
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => 'UNIT-ADMIN',
        ]);
        RolePermission::create([
            'role' => 'admin',
            'permission_id' => $permission->id,
        ]);

        $this->actingAs($admin)
            ->put(route('department.update', $first->kode), [
                'kode' => $first->kode,
                'nama' => '  kANTOR   pUSAT ',
                'deskripsi' => ' Updated   description ',
                'status' => true,
            ])
            ->assertRedirect(route('department.index'))
            ->assertSessionHas('success', 'Data unit kerja berhasil diedit');

        $this->assertDatabaseHas('departments', [
            'kode' => 'UNIT-01',
            'nama' => 'Kantor Pusat',
            'deskripsi' => 'Updated description',
        ]);

        $this->actingAs($admin)
            ->from(route('department.index'))
            ->put(route('department.update', $first->kode), [
                'kode' => 'UNIT-02',
                'nama' => 'Nama Baru',
                'status' => true,
            ])
            ->assertSessionHasErrors(['kode']);

        $this->actingAs($admin)
            ->from(route('department.index'))
            ->put(route('department.update', $first->kode), [
                'kode' => 'UNIT-01',
                'nama' => 'Kantor Cabang',
                'status' => true,
            ])
            ->assertSessionHasErrors(['nama']);
    }
}

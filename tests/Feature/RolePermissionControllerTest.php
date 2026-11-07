<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        $dept = Department::create(['kode' => 'SETDA', 'nama' => 'Sekretariat Daerah', 'status' => true]);
        return User::factory()->create([
            'role' => 'super_admin',
            'kode_unit_kerja' => $dept->kode,
        ]);
    }

    /**
     * Memeriksa selain Super Admin ditolak 403 saat mengakses manajemen hak akses.
     */
    public function test_non_super_admin_ditolak_mengakses_manajemen_hak_akses(): void
    {
        $dept = Department::create(['kode' => 'KEC-01', 'nama' => 'Kecamatan', 'status' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'kode_unit_kerja' => $dept->kode]);

        $response = $this->actingAs($admin)->get(route('setting.role-permissions.index'));

        $response->assertStatus(403);
    }

    /**
     * Memeriksa Super Admin dapat melihat halaman konfigurasi hak akses.
     */
    public function test_super_admin_dapat_melihat_halaman_manajemen_hak_akses(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $perm = Permission::create([
            'kode' => 'pegawai.view',
            'nama' => 'Lihat Pegawai',
            'modul' => 'pegawai',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('setting.role-permissions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('setting.role-permission.index');
        $response->assertViewHas('permissions');
        $response->assertViewHas('selectedPermissionIds');
    }

    /**
     * Memeriksa Super Admin berhasil memperbarui hak akses Admin.
     */
    public function test_super_admin_dapat_memperbarui_hak_akses_admin(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $perm1 = Permission::create(['kode' => 'pegawai.view', 'nama' => 'Lihat Pegawai', 'modul' => 'pegawai']);
        $perm2 = Permission::create(['kode' => 'pegawai.create', 'nama' => 'Tambah Pegawai', 'modul' => 'pegawai']);

        // Set hak akses awal
        RolePermission::create(['role' => 'admin', 'permission_id' => $perm1->id]);

        $payload = [
            'permission_ids' => [$perm2->id],
        ];

        $response = $this->actingAs($superAdmin)->put(route('setting.role-permissions.update'), $payload);

        $response->assertRedirect(route('setting.role-permissions.index'));
        $response->assertSessionHas('success', 'Hak akses Admin berhasil diperbarui.');

        // Hak akses lama dihapus, hak akses baru ditambahkan
        $this->assertDatabaseMissing('role_permissions', [
            'role' => 'admin',
            'permission_id' => $perm1->id,
        ]);

        $this->assertDatabaseHas('role_permissions', [
            'role' => 'admin',
            'permission_id' => $perm2->id,
        ]);
    }

    /**
     * Memeriksa validasi menolak jika mengirim kode izin warisan (legacy) atau modul hak_akses.
     */
    public function test_update_hak_akses_menolak_permission_legacy_atau_modul_hak_akses(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $legacyPerm = Permission::create([
            'kode' => 'pegawai.import', // Termasuk dalam legacyPermissionCodes
            'nama' => 'Import Pegawai',
            'modul' => 'pegawai',
        ]);

        $payload = [
            'permission_ids' => [$legacyPerm->id],
        ];

        $response = $this->actingAs($superAdmin)->put(route('setting.role-permissions.update'), $payload);

        $response->assertSessionHasErrors(['permission_ids']);
    }

    public function test_admin_cannot_submit_role_permission_changes(): void
    {
        $dept = Department::create([
            'kode' => 'KEC-UPD',
            'nama' => 'Kecamatan Pengujian',
            'status' => true,
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $dept->kode,
        ]);
        $permission = Permission::create([
            'kode' => 'pegawai.view',
            'nama' => 'Lihat Pegawai',
            'modul' => 'pegawai',
        ]);

        $this->actingAs($admin)
            ->put(route('setting.role-permissions.update'), [
                'permission_ids' => [$permission->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('role_permissions', [
            'role' => 'admin',
            'permission_id' => $permission->id,
        ]);
    }

    public function test_submitting_no_permission_selection_removes_all_admin_permissions(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $permission = Permission::create([
            'kode' => 'pegawai.view',
            'nama' => 'Lihat Pegawai',
            'modul' => 'pegawai',
        ]);
        RolePermission::create([
            'role' => 'admin',
            'permission_id' => $permission->id,
        ]);

        $this->actingAs($superAdmin)
            ->put(route('setting.role-permissions.update'), [])
            ->assertRedirect(route('setting.role-permissions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('role_permissions', [
            'role' => 'admin',
            'permission_id' => $permission->id,
        ]);
    }
}

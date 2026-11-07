<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_has_every_permission_without_a_grant(): void
    {
        $user = new User(['role' => 'super_admin']);

        $this->assertTrue($user->hasPermission('pegawai.view'));
    }

    public function test_other_roles_require_a_matching_permission_grant(): void
    {
        $user = new User(['role' => 'admin']);

        $this->assertFalse($user->hasPermission('pegawai.view'));

        $permission = Permission::create([
            'kode' => 'pegawai.view',
            'nama' => 'Lihat Data Pegawai',
            'modul' => 'pegawai',
        ]);
        RolePermission::create([
            'role' => 'admin',
            'permission_id' => $permission->id,
        ]);

        $this->assertTrue($user->hasPermission('pegawai.view'));
    }
}

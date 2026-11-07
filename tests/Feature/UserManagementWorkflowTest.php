<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UserManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function department(string $code): Department
    {
        return Department::create([
            'kode' => $code,
            'nama' => 'Unit ' . $code,
            'status' => true,
        ]);
    }

    private function grant(string $code): Permission
    {
        return Permission::create([
            'kode' => $code,
            'nama' => $code,
            'modul' => 'user',
        ]);
    }

    private function grantAdminPermissions(array $codes): void
    {
        foreach ($codes as $code) {
            $permission = $this->grant($code);
            RolePermission::create([
                'role' => 'admin',
                'permission_id' => $permission->id,
            ]);
        }
    }

    public function test_super_admin_lists_users_across_units_and_admin_is_scoped_to_own_unit(): void
    {
        $unitA = $this->department('USER-A');
        $unitB = $this->department('USER-B');
        $ownUser = User::factory()->create([
            'role' => 'user',
            'kode_unit_kerja' => $unitA->kode,
        ]);
        $otherUser = User::factory()->create([
            'role' => 'user',
            'kode_unit_kerja' => $unitB->kode,
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $unitA->kode,
        ]);
        $this->grantAdminPermissions(['user.view']);

        $adminResponse = $this->actingAs($admin)->get(route('user.index'));
        $adminResponse->assertOk()->assertViewIs('user.index');
        $this->assertSame(
            [$ownUser->id],
            $adminResponse->viewData('data')->pluck('id')->all()
        );

        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $superAdminResponse = $this->actingAs($superAdmin)->get(route('user.index'));
        $superAdminResponse->assertOk();
        $this->assertEqualsCanonicalizing(
            [$ownUser->id, $otherUser->id],
            $superAdminResponse->viewData('data')
                ->where('role', 'user')
                ->pluck('id')
                ->all()
        );
    }

    public function test_user_role_cannot_open_user_management_without_admin_permission(): void
    {
        $this->department('USER-C');
        $user = User::factory()->create([
            'role' => 'user',
            'kode_unit_kerja' => 'USER-C',
        ]);

        $this->actingAs($user)
            ->get(route('user.index'))
            ->assertForbidden();
    }

    public function test_admin_can_reset_only_a_user_account_in_the_same_unit(): void
    {
        $unitA = $this->department('USER-D');
        $unitB = $this->department('USER-E');
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $unitA->kode,
        ]);
        $this->grantAdminPermissions([
            'user.view',
            'user.reset_password',
        ]);
        $ownUser = User::factory()->create([
            'role' => 'user',
            'kode_unit_kerja' => $unitA->kode,
            'password' => 'old-password',
        ]);
        $otherUser = User::factory()->create([
            'role' => 'user',
            'kode_unit_kerja' => $unitB->kode,
        ]);

        $this->actingAs($admin)
            ->put(route('user.reset-password', $ownUser->id))
            ->assertSessionHas('success');
        $this->assertTrue(Hash::check(
            '123456789',
            $ownUser->fresh()->password
        ));

        $this->actingAs($admin)
            ->put(route('user.reset-password', $otherUser->id))
            ->assertNotFound();
        $this->actingAs($admin)
            ->put(route('user.reset-password', 999999))
            ->assertNotFound();
    }

    public function test_super_admin_creates_admin_and_cannot_be_spoofed_into_creating_user_role(): void
    {
        $department = $this->department('USER-F');
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $anotherSuperAdmin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'shared@example.test',
        ]);
        $payload = [
            'nama' => '  admin   unit baru ',
            'username' => 'admin-unit-f',
            'email' => $anotherSuperAdmin->email,
            'no_hp' => '081234567890',
            'kode_unit_kerja' => $department->kode,
            'role' => 'user',
        ];

        $this->from(route('user.index'))
            ->actingAs($superAdmin)
            ->post(route('user.store'), $payload)
            ->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('users', [
            'username' => $payload['username'],
        ]);

        $payload['email'] = 'admin-f@example.test';
        $this->actingAs($superAdmin)
            ->post(route('user.store'), $payload)
            ->assertRedirect(route('user.index'))
            ->assertSessionHas('success');

        $created = User::query()->where('username', $payload['username'])->firstOrFail();
        $this->assertSame('admin', $created->role);
        $this->assertSame('Admin Unit Baru', $created->nama);
        $this->assertTrue(Hash::check('123456789', $created->password));

        unset($payload['role']);
        $this->from(route('user.index'))
            ->actingAs($superAdmin)
            ->post(route('user.store'), $payload)
            ->assertSessionHasErrors(['username', 'kode_unit_kerja']);
    }

    public function test_super_admin_updates_profile_without_changing_role_or_employee_identity(): void
    {
        $department = $this->department('USER-G');
        Employee::create([
            'nip' => '199001012020011001',
            'nama' => 'Pegawai Penghubung',
        ]);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create([
            'role' => 'admin',
            'nip' => '199001012020011001',
            'kode_unit_kerja' => $department->kode,
        ]);

        $this->actingAs($superAdmin)
            ->put(route('user.update', $admin->id), [
                'nama' => 'Admin Diperbarui',
                'username' => 'admin-updated',
                'email' => 'updated@example.test',
                'no_hp' => '081234567891',
                'kode_unit_kerja' => $department->kode,
                'role' => 'user',
                'nip' => '199001012020011999',
            ])
            ->assertRedirect();

        $admin->refresh();
        $this->assertSame('Admin Diperbarui', $admin->nama);
        $this->assertSame('admin-updated', $admin->username);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('199001012020011001', $admin->nip);
    }

    public function test_super_admin_can_update_synced_user_without_changing_role_or_employee_link(): void
    {
        $department = $this->department('USER-SYNCED');
        Employee::create([
            'nip' => '199001012020011002',
            'nama' => 'Pegawai Tersinkron',
        ]);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create([
            'role' => 'user',
            'nip' => '199001012020011002',
            'kode_unit_kerja' => $department->kode,
            'nama' => 'User Lama',
        ]);

        $this->actingAs($superAdmin)
            ->put(route('user.update', $user->id), [
                'nama' => 'User Diperbarui',
                'username' => 'synced-user-updated',
                'email' => 'synced-user@example.test',
                'no_hp' => '081234567892',
                'kode_unit_kerja' => $department->kode,
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('User Diperbarui', $user->nama);
        $this->assertSame('user', $user->role);
        $this->assertSame('199001012020011002', $user->nip);
        $this->assertSame($department->kode, $user->kode_unit_kerja);
    }

    public function test_profile_photo_is_optional_and_replacement_removes_old_public_file(): void
    {
        Storage::fake('public');
        $department = $this->department('USER-PHOTO');
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $department->kode,
            'foto' => 'pengguna/old.jpg',
            'nama' => 'Admin Foto',
            'username' => 'admin-foto',
            'email' => 'admin-foto@example.test',
            'no_hp' => '081234567890',
        ]);
        Storage::disk('public')->put($admin->foto, 'old photo');
        $payload = [
            'nama' => $admin->nama,
            'username' => $admin->username,
            'email' => $admin->email,
            'no_hp' => $admin->no_hp,
            'kode_unit_kerja' => $department->kode,
        ];

        $this->actingAs($superAdmin)
            ->put(route('user.update', $admin->id), $payload)
            ->assertRedirect();
        Storage::disk('public')->assertExists('pengguna/old.jpg');

        $photoResponse = $this->actingAs($superAdmin)
            ->put(route('user.update', $admin->id), $payload + [
                'foto' => UploadedFile::fake()->image('new.jpg'),
            ]);
        $photoResponse->assertRedirect()->assertSessionHasNoErrors();

        $newPhoto = $admin->fresh()->foto;
        $this->assertNotSame('pengguna/old.jpg', $newPhoto);
        Storage::disk('public')->assertMissing('pengguna/old.jpg');
        Storage::disk('public')->assertExists($newPhoto);
    }

    public function test_user_role_permissions_are_not_changed_when_admin_permissions_are_updated(): void
    {
        $department = $this->department('USER-H');
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'kode_unit_kerja' => $department->kode,
        ]);
        $adminPermission = $this->grant('pegawai.view');
        $userPermission = $this->grant('profile.view');
        RolePermission::create([
            'role' => 'user',
            'permission_id' => $userPermission->id,
        ]);

        $this->actingAs($superAdmin)
            ->put(route('setting.role-permissions.update'), [
                'permission_ids' => [$adminPermission->id],
            ])
            ->assertRedirect(route('setting.role-permissions.index'));

        $this->assertDatabaseHas('role_permissions', [
            'role' => 'user',
            'permission_id' => $userPermission->id,
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role' => 'admin',
            'permission_id' => $adminPermission->id,
        ]);
    }

    public function test_failed_user_insert_cleans_up_uploaded_profile_photo(): void
    {
        Storage::fake('public');
        $department = $this->department('USER-PHOTO-INSERT-FAIL');
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        DB::statement(
            "CREATE TRIGGER reject_photo_user_insert BEFORE INSERT ON users "
            . "WHEN NEW.username = 'orphan-upload' "
            . "BEGIN SELECT RAISE(FAIL, 'user insert failed'); END"
        );

        $this->actingAs($superAdmin)
            ->post(route('user.store'), [
                'nama' => 'Foto Gagal',
                'username' => 'orphan-upload',
                'email' => 'orphan@example.test',
                'no_hp' => '081234567890',
                'kode_unit_kerja' => $department->kode,
                'foto' => UploadedFile::fake()->image('profile.png'),
            ])
            ->assertSessionHas('danger');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_user_update_cleans_up_new_photo_and_keeps_old_photo(): void
    {
        Storage::fake('public');
        $department = $this->department('USER-PHOTO-UPDATE-FAIL');
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create([
            'role' => 'admin',
            'kode_unit_kerja' => $department->kode,
            'foto' => 'pengguna/old.jpg',
            'nama' => 'Admin Foto Failure',
            'username' => 'admin-foto-failure',
            'email' => 'admin-foto-failure@example.test',
            'no_hp' => '081234567893',
        ]);
        Storage::disk('public')->put('pengguna/old.jpg', 'old photo');
        DB::statement(
            'CREATE TRIGGER reject_photo_user_update BEFORE UPDATE ON users '
            . 'WHEN OLD.id = ' . (int) $admin->id . ' '
            . "BEGIN SELECT RAISE(FAIL, 'user update failed'); END"
        );

        $response = $this->actingAs($superAdmin)
            ->put(route('user.update', $admin->id), [
                'nama' => $admin->nama,
                'username' => $admin->username,
                'email' => $admin->email,
                'no_hp' => $admin->no_hp,
                'kode_unit_kerja' => $department->kode,
                'foto' => UploadedFile::fake()->image('replacement.png'),
            ]);
        $response->assertRedirect()->assertSessionHasErrors(
            ['error'],
            null,
            'edit'
        );

        $this->assertSame('pengguna/old.jpg', $admin->fresh()->foto);
        $this->assertSame(
            ['pengguna/old.jpg'],
            Storage::disk('public')->allFiles()
        );
    }
}

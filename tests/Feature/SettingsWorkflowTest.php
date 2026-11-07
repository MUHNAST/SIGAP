<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role = 'user', array $attributes = []): User
    {
        $departmentCode = 'SET-' . Str::upper(Str::random(8));
        Department::create([
            'kode' => $departmentCode,
            'nama' => 'Unit ' . $departmentCode,
            'status' => true,
        ]);
        if ($role === 'user' && ! array_key_exists('nip', $attributes)) {
            $employee = Employee::create([
                'nip' => str_pad((string) random_int(1, 999999999999999999), 18, '0', STR_PAD_LEFT),
                'nama' => 'Pegawai Pengaturan',
            ]);
            $attributes['nip'] = $employee->nip;
        }

        return User::factory()->create(array_merge([
            'role' => $role,
            'nama' => 'Pengguna Pengaturan',
            'password' => Hash::make('old-password'),
            'kode_unit_kerja' => $departmentCode,
        ], $attributes));
    }

    public function test_settings_page_shows_only_current_users_login_and_activity_history(): void
    {
        $user = $this->createUser();
        $other = $this->createUser('admin');
        ActivityLog::create([
            'id_user' => $user->id,
            'aktivitas' => 'Login',
            'deskripsi' => 'Riwayat login sendiri',
            'ip_address' => '192.0.2.1',
            'user_agent' => 'Settings test browser',
        ]);
        ActivityLog::create([
            'id_user' => $other->id,
            'aktivitas' => 'Login',
            'deskripsi' => 'Riwayat pengguna lain',
        ]);

        $response = $this->actingAs($user)->get(route('setting.index'));

        $response->assertOk()
            ->assertViewIs('setting.index')
            ->assertViewHas('lastLogin', fn ($login) => $login?->id_user === $user->id)
            ->assertSee('Riwayat login sendiri')
            ->assertDontSee('Riwayat pengguna lain');
    }

    public function test_all_supported_roles_can_access_their_account_settings(): void
    {
        foreach (['super_admin', 'admin', 'user'] as $role) {
            $user = $this->createUser($role);

            $this->actingAs($user)
                ->get(route('setting.index'))
                ->assertOk()
                ->assertViewHas('user', fn ($viewUser) => $viewUser->is($user));
        }
    }

    public function test_guest_cannot_access_settings(): void
    {
        $this->get(route('setting.index'))
            ->assertRedirect(route('login'));
    }

    public function test_profile_update_persists_values_and_logs_the_change(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->put(route('setting.account.update'), [
                'nama' => 'Nama Baru Pengguna',
                'email' => 'updated@example.test',
                'no_hp' => '081234567890',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Profil berhasil diperbarui.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nama' => 'Nama Baru Pengguna',
            'email' => 'updated@example.test',
            'no_hp' => '081234567890',
            'role' => 'user',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $user->id,
            'aktivitas' => 'Update Profil',
        ]);
    }

    public function test_profile_validation_rejects_duplicate_email_and_invalid_phone_boundary(): void
    {
        $user = $this->createUser();
        $this->createUser('admin', ['email' => 'taken@example.test']);

        $this->actingAs($user)
            ->from(route('setting.index'))
            ->put(route('setting.account.update'), [
                'nama' => 'Nama Pengguna Baru',
                'email' => 'taken@example.test',
                'no_hp' => '081234567',
            ])
            ->assertRedirect(route('setting.index'))
            ->assertSessionHasErrors(['email', 'no_hp']);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'email' => 'taken@example.test',
        ]);
    }

    public function test_photo_update_and_reset_keep_account_and_employee_photos_consistent(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profiles/old.jpg', 'old image');
        Storage::disk('public')->put('employees/old.jpg', 'old employee image');
        $employee = Employee::create([
            'nip' => '199001012020011099',
            'nama' => 'Pegawai Pengaturan',
            'foto' => 'employees/old.jpg',
        ]);
        $user = $this->createUser('user', [
            'nip' => $employee->nip,
            'foto' => 'profiles/old.jpg',
        ]);

        $this->actingAs($user)
            ->put(route('setting.photo.update'), [
                'foto' => UploadedFile::fake()->image('profile.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Foto profil berhasil diperbarui.');

        $user->refresh();
        $employee->refresh();
        $this->assertSame($user->foto, $employee->foto);
        Storage::disk('public')->assertExists($user->foto);
        Storage::disk('public')->assertMissing('profiles/old.jpg');
        Storage::disk('public')->assertMissing('employees/old.jpg');

        $updatedPhoto = $user->foto;
        $this->delete(route('setting.photo.reset'))
            ->assertRedirect()
            ->assertSessionHas('success', 'Foto profil berhasil direset.');

        $this->assertSame('images/default-user.png', $user->fresh()->foto);
        $this->assertSame('images/default-user.png', $employee->fresh()->foto);
        Storage::disk('public')->assertMissing($updatedPhoto);
    }

    public function test_photo_validation_rejects_non_image_and_oversized_upload(): void
    {
        Storage::fake('public');
        $user = $this->createUser('user', ['foto' => 'images/default-user.png']);

        $this->actingAs($user)
            ->from(route('setting.index'))
            ->put(route('setting.photo.update'), [])
            ->assertRedirect(route('setting.index'))
            ->assertSessionHasErrors('foto');

        $this->actingAs($user)
            ->from(route('setting.index'))
            ->put(route('setting.photo.update'), [
                'foto' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('setting.index'))
            ->assertSessionHasErrors('foto');

        $this->actingAs($user)
            ->from(route('setting.index'))
            ->put(route('setting.photo.update'), [
                'foto' => UploadedFile::fake()->image('large.jpg')->size(2049),
            ])
            ->assertRedirect(route('setting.index'))
            ->assertSessionHasErrors('foto');

        $this->assertSame('images/default-user.png', $user->fresh()->foto);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_password_update_checks_old_password_and_changes_hash(): void
    {
        $user = $this->createUser();
        $oldRememberToken = $user->remember_token;

        $this->actingAs($user)
            ->from(route('setting.index'))
            ->put(route('setting.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
                'active_tab' => 'security',
            ])
            ->assertRedirect(route('setting.index'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));

        $this->put(route('setting.password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
            'active_tab' => 'security',
        ])->assertRedirect()
            ->assertSessionHas('success', 'Password berhasil diperbarui.')
            ->assertSessionHas('active_tab', 'security');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertNotSame($oldRememberToken, $user->fresh()->remember_token);
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_update_rejects_confirmation_mismatch_and_reusing_current_password(): void
    {
        $user = $this->createUser();
        $this->actingAs($user)
            ->from(route('setting.index'))
            ->put(route('setting.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'different-password',
            ])
            ->assertRedirect(route('setting.index'))
            ->assertSessionHasErrors('password');

        $this->put(route('setting.password.update'), [
            'current_password' => 'old-password',
            'password' => 'old-password',
            'password_confirmation' => 'old-password',
        ])->assertRedirect(route('setting.index'))
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_email_two_factor_requires_password_and_email_and_changes_login_policy(): void
    {
        Mail::fake();
        $department = Department::create([
            'kode' => 'SETTINGS-UNIT',
            'nama' => 'Unit Pengaturan',
            'status' => true,
        ]);
        $user = $this->createUser('user', [
            'kode_unit_kerja' => $department->kode,
            'email' => 'settings-2fa@example.test',
            'email_2fa_enabled' => false,
        ]);

        $this->actingAs($user)
            ->from(route('setting.index'))
            ->post(route('setting.two-factor.enable'), [
                'current_password' => 'wrong-password',
            ])
            ->assertRedirect(route('setting.index'))
            ->assertSessionHasErrors('current_password');
        $this->assertFalse((bool) $user->fresh()->email_2fa_enabled);

        $this->post(route('setting.two-factor.enable'), [
            'current_password' => 'old-password',
        ])->assertRedirect()
            ->assertSessionHas('success', 'Verifikasi dua langkah melalui email berhasil diaktifkan.');
        $this->assertTrue((bool) $user->fresh()->email_2fa_enabled);

        Auth::logout();
        $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'old-password',
        ])->assertRedirect(route('auth.two-factor.challenge'));
        $this->assertGuest();
        Mail::assertSent(\App\Mail\EmailOtpMail::class);
    }

    public function test_two_factor_can_be_disabled_and_invalid_repeat_is_rejected(): void
    {
        $user = $this->createUser('user', ['email_2fa_enabled' => true]);
        $this->actingAs($user)
            ->from(route('setting.index'))
            ->delete(route('setting.two-factor.disable'), [
                'current_password' => 'wrong-password',
            ])
            ->assertRedirect(route('setting.index'))
            ->assertSessionHasErrors('current_password');
        $this->assertTrue((bool) $user->fresh()->email_2fa_enabled);

        $this->delete(route('setting.two-factor.disable'), [
            'current_password' => 'old-password',
        ])->assertRedirect()
            ->assertSessionHas('success', 'Verifikasi dua langkah berhasil dinonaktifkan.');
        $this->assertFalse((bool) $user->fresh()->email_2fa_enabled);
    }

    public function test_email_two_factor_cannot_be_enabled_without_an_email_address(): void
    {
        $user = $this->createUser('user', ['email' => null]);

        $this->actingAs($user)
            ->from(route('setting.index'))
            ->post(route('setting.two-factor.enable'), [
                'current_password' => 'old-password',
            ])
            ->assertRedirect(route('setting.index'))
            ->assertSessionHasErrors('current_password');

        $this->assertFalse((bool) $user->fresh()->email_2fa_enabled);
    }

    public function test_logout_devices_rejects_wrong_password_and_regenerates_remember_token(): void
    {
        $user = $this->createUser();
        $rememberToken = $user->remember_token;

        $this->followingRedirects()
            ->actingAs($user)
            ->from(route('setting.index'))
            ->post(route('setting.logout.devices'), [
                'current_password' => 'wrong-password',
            ])
            ->assertOk()
            ->assertViewHas('errors', fn ($errors) => $errors->getBag('logout')->has('current_password'))
            ->assertSee('Password yang dimasukkan salah.')
            ->assertSee('modalLogoutDevices');
        $this->assertSame($rememberToken, $user->fresh()->remember_token);

        $this->post(route('setting.logout.devices'), [
            'current_password' => 'old-password',
        ])->assertRedirect()
            ->assertSessionHas('success', 'Berhasil logout dari seluruh perangkat.');
        $this->assertNotSame($rememberToken, $user->fresh()->remember_token);
        $this->assertAuthenticatedAs($user);
    }
}

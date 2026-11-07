<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $service;
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(UserRepository::class);
        $this->service = new UserService($this->repository);
    }

    /**
     * Memeriksa authorizePermission melempar AuthorizationException jika belum login.
     */
    public function test_authorize_permission_melempar_exception_jika_belum_login(): void
    {
        $reflection = new \ReflectionClass(UserService::class);
        $method = $reflection->getMethod('authorizePermission');
        $method->setAccessible(true);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Anda harus login untuk melakukan tindakan ini.');

        $method->invoke($this->service, 'user.view');
    }

    /**
     * Memeriksa Super Admin selalu lolos pengecekan permission (bypass).
     */
    public function test_authorize_permission_lolos_untuk_super_admin(): void
    {
        $superAdmin = new User(['role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $reflection = new \ReflectionClass(UserService::class);
        $method = $reflection->getMethod('authorizePermission');
        $method->setAccessible(true);

        $actor = $method->invoke($this->service, 'user.view');
        $this->assertSame('super_admin', $actor->role);
    }

    /**
     * Memeriksa Admin ditolak jika tidak memiliki permission tertentu.
     */
    public function test_authorize_permission_menolak_admin_tanpa_permission(): void
    {
        $admin = Mockery::mock(User::class)->makePartial();
        $admin->role = 'admin';
        $admin->shouldReceive('hasPermission')->with('user.view')->andReturn(false);

        $this->actingAs($admin);

        $reflection = new \ReflectionClass(UserService::class);
        $method = $reflection->getMethod('authorizePermission');
        $method->setAccessible(true);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Anda tidak memiliki hak akses untuk melakukan tindakan ini.');

        $method->invoke($this->service, 'user.view');
    }

    /**
     * Memeriksa Admin lolos jika memiliki permission.
     */
    public function test_authorize_permission_lolos_untuk_admin_dengan_permission(): void
    {
        $admin = Mockery::mock(User::class)->makePartial();
        $admin->role = 'admin';
        $admin->shouldReceive('hasPermission')->with('user.view')->andReturn(true);

        $this->actingAs($admin);

        $reflection = new \ReflectionClass(UserService::class);
        $method = $reflection->getMethod('authorizePermission');
        $method->setAccessible(true);

        $actor = $method->invoke($this->service, 'user.view');
        $this->assertSame('admin', $actor->role);
    }

    /**
     * Memeriksa Admin hanya dapat mengakses akun User di unit kerjanya sendiri.
     */
    public function test_authorize_user_access_admin_hanya_bisa_mengakses_user_unit_sendiri(): void
    {
        $admin = Mockery::mock(User::class)->makePartial();
        $admin->role = 'admin';
        $admin->kode_unit_kerja = 'UNIT-10';
        $admin->shouldReceive('hasPermission')->with('user.reset_password')->andReturn(true);

        $this->actingAs($admin);

        $reflection = new \ReflectionClass(UserService::class);
        $method = $reflection->getMethod('authorizeUserAccess');
        $method->setAccessible(true);

        // 1. User di unit yang sama -> Lolos
        $sameUnitUser = new User(['role' => 'user', 'kode_unit_kerja' => 'UNIT-10']);
        $result = $method->invoke($this->service, $sameUnitUser, 'user.reset_password');
        $this->assertSame($admin, $result);

        // 2. User di unit berbeda -> Exception
        $diffUnitUser = new User(['role' => 'user', 'kode_unit_kerja' => 'UNIT-99']);
        try {
            $method->invoke($this->service, $diffUnitUser, 'user.reset_password');
            $this->fail('Seharusnya melempar AuthorizationException');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('Anda tidak memiliki hak akses terhadap data pengguna tersebut.', $e->getMessage());
        }

        // 3. Target sesama Admin -> Exception
        $otherAdmin = new User(['role' => 'admin', 'kode_unit_kerja' => 'UNIT-10']);
        try {
            $method->invoke($this->service, $otherAdmin, 'user.reset_password');
            $this->fail('Seharusnya melempar AuthorizationException');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('Anda tidak memiliki hak akses terhadap data pengguna tersebut.', $e->getMessage());
        }
    }

    /**
     * Memeriksa pembuatan akun admin menolak unit kerja yang sudah digunakan akun istimewa.
     */
    public function test_create_admin_menolak_unit_kerja_yang_sudah_digunakan(): void
    {
        $superAdmin = new User(['role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $this->repository->shouldReceive('isUnitUsedByPrivilegedRole')
            ->once()
            ->with('UNIT-01')
            ->andReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unit kerja tersebut sudah digunakan oleh akun Admin atau Super Admin.');

        $this->service->create([
            'nama' => 'Admin Baru',
            'username' => 'adminbaru',
            'email' => 'admin@test.com',
            'no_hp' => '081234567890',
            'kode_unit_kerja' => 'UNIT-01',
        ]);
    }

    /**
     * Memeriksa pembuatan akun admin berhasil dengan password default jika data valid.
     */
    public function test_create_admin_berhasil_dengan_password_default(): void
    {
        $superAdmin = new User(['role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $this->repository->shouldReceive('isUnitUsedByPrivilegedRole')
            ->once()
            ->with('UNIT-02')
            ->andReturn(false);

        $createdUser = new User();
        $createdUser->id = 5;
        $createdUser->username = 'adminbaru';

        $this->repository->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['role'] === 'admin'
                    && Hash::check('123456789', $data['password'])
                    && $data['kode_unit_kerja'] === 'UNIT-02';
            })
            ->andReturn($createdUser);

        $result = $this->service->create([
            'nama' => 'Admin Baru',
            'username' => 'adminbaru',
            'email' => 'admin@test.com',
            'no_hp' => '081234567890',
            'kode_unit_kerja' => 'UNIT-02',
        ]);

        $this->assertSame(5, $result->id);
    }

    /**
     * Memeriksa penghapusan akun Super Admin dilarang.
     */
    public function test_delete_user_menolak_menghapus_super_admin(): void
    {
        $actor = new User(['id' => 1, 'role' => 'super_admin']);
        $this->actingAs($actor);

        $targetSuperAdmin = new User(['id' => 2, 'role' => 'super_admin']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Akun Super Admin tidak dapat dihapus.');

        $this->service->delete($targetSuperAdmin);
    }

    /**
     * Memeriksa pengguna dilarang menghapus akun Super Admin (termasuk akun sendiri).
     */
    public function test_delete_user_menolak_menghapus_akun_super_admin_sendiri(): void
    {
        $actor = new User(['role' => 'super_admin']);
        $actor->id = 3;
        $this->actingAs($actor);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Akun Super Admin tidak dapat dihapus.');

        $this->service->delete($actor);
    }

    /**
     * Memeriksa selain Super Admin tidak boleh menghapus akun pengguna.
     */
    public function test_delete_user_menolak_role_non_super_admin(): void
    {
        $actor = new User(['id' => 4, 'role' => 'admin']);
        $this->actingAs($actor);

        $target = new User(['id' => 10, 'role' => 'user']);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Anda tidak memiliki hak akses untuk menghapus pengguna.');

        $this->service->delete($target);
    }
}

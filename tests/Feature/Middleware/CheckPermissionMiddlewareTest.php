<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\CheckPermission;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CheckPermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private CheckPermission $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckPermission();
    }

    /**
     * Memeriksa pengguna yang belum login dialihkan ke halaman login.
     */
    public function test_guest_dialihkan_ke_login(): void
    {
        $request = Request::create('/employees', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'pegawai.view');

        $this->assertTrue($response->isRedirection());
        $this->assertSame(route('login'), $response->headers->get('Location'));
    }

    /**
     * Memeriksa Super Admin selalu lolos tanpa memeriksa tabel role_permissions (bypass).
     */
    public function test_super_admin_selalu_lolos_pemeriksaan_permission(): void
    {
        $superAdmin = new User(['role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $request = Request::create('/employees', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('Super Admin Lolos');
        }, 'pegawai.view');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Super Admin Lolos', $response->getContent());
    }

    /**
     * Memeriksa pengguna dengan role user ditolak 403 saat mengakses rute berizin operasional.
     */
    public function test_role_user_selalu_ditolak_403(): void
    {
        $user = new User(['role' => 'user']);
        $this->actingAs($user);

        $request = Request::create('/employees', 'GET');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Akses ditolak: Anda tidak memiliki izin.');

        $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'pegawai.view');
    }

    /**
     * Memeriksa Admin ditolak 403 jika izin belum diberikan di tabel role_permissions.
     */
    public function test_admin_ditolak_jika_tidak_memiliki_permission(): void
    {
        $admin = new User(['role' => 'admin']);
        $this->actingAs($admin);

        $request = Request::create('/employees', 'GET');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Akses ditolak: Anda tidak memiliki izin.');

        $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'pegawai.create');
    }

    /**
     * Memeriksa Admin diizinkan jika permission telah dikonfigurasi di tabel role_permissions.
     */
    public function test_admin_lolos_jika_memiliki_permission_yang_sesuai(): void
    {
        $admin = new User(['role' => 'admin']);
        $this->actingAs($admin);

        $perm = Permission::create([
            'kode' => 'pegawai.view',
            'nama' => 'Lihat Data Pegawai',
            'modul' => 'pegawai',
        ]);

        RolePermission::create([
            'role' => 'admin',
            'permission_id' => $perm->id,
        ]);

        $request = Request::create('/employees', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('Admin Diizinkan');
        }, 'pegawai.view');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Admin Diizinkan', $response->getContent());
    }
}

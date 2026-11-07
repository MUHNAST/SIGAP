<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\CheckRole;
use App\Models\LoginDevice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CheckRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private CheckRole $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckRole();
    }

    /**
     * Memeriksa pengguna yang belum terautentikasi (guest) dialihkan ke route login.
     */
    public function test_guest_dialihkan_ke_login(): void
    {
        $request = Request::create('/dashboard', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'super_admin');

        $this->assertTrue($response->isRedirection());
        $this->assertSame(route('login'), $response->headers->get('Location'));
    }

    /**
     * Memeriksa pengguna dengan role yang tidak terdaftar pada parameter ditolak 403.
     */
    public function test_role_tidak_sesuai_menghasilkan_abort_403(): void
    {
        $user = new User(['role' => 'user']);
        $this->actingAs($user);

        $request = Request::create('/dashboard', 'GET');

        $session = app('session')->driver();
        $session->start();
        $request->setLaravelSession($session);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Akses ditolak: Anda tidak memiliki izin.');

        $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'super_admin', 'admin');
    }

    /**
     * Memeriksa pengguna dengan role yang sesuai diizinkan melanjutkan request dan memperbarui aktivitas perangkat.
     */
    public function test_role_sesuai_lolos_dan_memperbarui_login_device(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $request = Request::create('/dashboard', 'GET');

        $session = app('session')->driver();
        $session->start();
        $request->setLaravelSession($session);

        $device = LoginDevice::create([
            'id_user' => $user->id,
            'device_fingerprint' => 'test-fp',
            'session_id' => $session->getId(),
            'ip_address' => '127.0.0.1',
            'browser' => 'PHPUnit',
            'aktivitas_terakhir' => now()->subHours(2),
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Akses Diberikan');
        }, 'super_admin', 'admin');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Akses Diberikan', $response->getContent());

        // Memastikan aktivitas terakhir login device terupdate
        $device->refresh();
        $this->assertTrue(Carbon::parse($device->aktivitas_terakhir)->greaterThan(now()->subMinute()));
    }
}

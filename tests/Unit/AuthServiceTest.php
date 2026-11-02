<?php

namespace Tests\Unit;

// use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    private function makeService(?User $user): AuthService
    {
        $repository = Mockery::mock(UserRepository::class);

        $repository->shouldReceive('getByUsername')
            ->once()
            ->with('admin')
            ->andReturn($user);

        return new AuthService($repository);
    }

    public function test_username_tidak_ditemukan(): void
    {
        $service = $this->makeService(null);

        try {
            $service->login([
                'username' => 'admin',
                'password' => 'password123',
            ]);

            $this->fail('Seharusnya login ditolak.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Username tidak ditemukan.',
                $e->errors()['username'][0]
            );
        }
    }

    public function test_unit_kerja_tidak_valid(): void
    {
        $user = new User([
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'kode_unit_kerja' => null,
            'department' => 'IT',
        ]);

        $service = $this->makeService($user);

        try {
            $service->login([
                'username' => 'admin',
                'password' => 'password123',
            ]);

            $this->fail('Seharusnya login ditolak.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Unit Kerja akun tidak valid.',
                $e->errors()['username'][0]
            );
        }
    }

    public function test_password_salah(): void
    {
        $user = new User([
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'kode_unit_kerja' => 'UNIT01',
            'department' => 'IT',
        ]);

        $service = $this->makeService($user);

        try {
            $service->login([
                'username' => 'admin',
                'password' => 'password_salah',
            ]);

            $this->fail('Seharusnya login ditolak.');
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->all();

            $this->assertNotEmpty($messages);
        }
    }
    public function test_logout_mengakhiri_autentikasi(): void
{
    $session = app('session')->driver();
    $session->start();

    request()->setLaravelSession($session);

    /** @var User $user */
    $user = User::factory()->create();

    $this->actingAs($user);
    $this->assertAuthenticatedAs($user);

    app(AuthService::class)->logout();

    $this->assertGuest();
}

}

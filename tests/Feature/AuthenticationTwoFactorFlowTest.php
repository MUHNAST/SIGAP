<?php

namespace Tests\Feature;

use App\Mail\EmailOtpMail;
use App\Models\Department;
use App\Models\LoginDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTwoFactorFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(
        string $role = 'admin',
        bool $twoFactorEnabled = false,
        ?string $email = 'auth@example.test'
    ): User {
        Department::firstOrCreate(['kode' => 'AUTH-01'], [
            'nama' => 'Unit Autentikasi',
            'status' => true,
        ]);

        return User::create([
            'username' => 'auth-user-' . uniqid(),
            'email' => $email,
            'nama' => 'Pengguna Autentikasi',
            'role' => $role,
            'kode_unit_kerja' => 'AUTH-01',
            'email_2fa_enabled' => $twoFactorEnabled,
            'password' => bcrypt('password-123'),
        ]);
    }

    public function test_guest_root_shows_login_page(): void
    {
        $this->get('/')->assertOk();
    }

    #[DataProvider('supportedDashboardRoles')]
    public function test_root_redirects_supported_role_to_its_dashboard(
        string $role,
        string $dashboard
    ): void
    {
        $user = $this->createUser($role);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route($dashboard));
    }

    public static function supportedDashboardRoles(): iterable
    {
        yield 'super admin' => ['super_admin', 'dashboard'];
        yield 'admin' => ['admin', 'dashboard'];
        yield 'user' => ['user', 'dashboard_user'];
    }

    public function test_root_logs_out_an_unsupported_role(): void
    {
        $user = $this->createUser('unknown-role');

        $response = $this->actingAs($user)->get('/');

        $this->assertSame(
            route('login'),
            $response->headers->get('Location')
        );
        $this->assertNotEmpty($response->getSession()->get('errors'));

        $this->assertGuest();
    }

    #[DataProvider('nonTwoFactorDashboardRoles')]
    public function test_valid_non_2fa_login_authenticates_and_redirects_by_role(
        string $role,
        string $dashboard
    ): void
    {
        $user = $this->createUser($role);

        $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'password-123',
            'remember' => 'yes',
        ])->assertRedirect(route($dashboard));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('login_devices', [
            'id_user' => $user->id,
        ]);
    }

    public static function nonTwoFactorDashboardRoles(): iterable
    {
        yield 'super admin' => ['super_admin', 'dashboard'];
        yield 'admin' => ['admin', 'dashboard'];
        yield 'user' => ['user', 'dashboard_user'];
    }

    public function test_non_two_factor_login_without_remember_does_not_set_remember_token(): void
    {
        $user = $this->createUser('user');

        $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'password-123',
        ])->assertRedirect(route('dashboard_user'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'remember_token' => null,
        ]);
    }

    public function test_non_two_factor_login_rejects_an_unsupported_role(): void
    {
        $user = $this->createUser('unknown-role');

        $response = $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'password-123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_new_device_is_rejected_when_two_devices_are_already_registered(): void
    {
        $user = $this->createUser();
        $headers = [
            'User-Agent' => 'SIGAP test browser',
            'Accept-Language' => 'id-ID',
            'Sec-CH-UA' => '"Browser"',
            'Sec-CH-UA-Platform' => '"Windows"',
        ];
        LoginDevice::create([
            'id_user' => $user->id,
            'device_fingerprint' => 'known-device-one',
        ]);
        LoginDevice::create([
            'id_user' => $user->id,
            'device_fingerprint' => 'known-device-two',
        ]);

        $response = $this->withHeaders($headers)
            ->from(route('login'))
            ->post(route('login.process'), [
                'username' => $user->username,
                'password' => 'password-123',
            ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_registered_device_can_login_at_two_device_limit(): void
    {
        $user = $this->createUser();
        $headers = [
            'User-Agent' => 'SIGAP test browser',
            'Accept-Language' => 'id-ID',
            'Sec-CH-UA' => '"Browser"',
            'Sec-CH-UA-Platform' => '"Windows"',
        ];
        $fingerprint = hash('sha256', implode('|', [
            $headers['User-Agent'],
            $headers['Accept-Language'],
            $headers['Sec-CH-UA'],
            $headers['Sec-CH-UA-Platform'],
        ]));
        LoginDevice::create([
            'id_user' => $user->id,
            'device_fingerprint' => $fingerprint,
        ]);
        LoginDevice::create([
            'id_user' => $user->id,
            'device_fingerprint' => 'another-device',
        ]);

        $response = $this->withHeaders($headers)
            ->post(route('login.process'), [
                'username' => $user->username,
                'password' => 'password-123',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('login_devices', 2);
    }

    public function test_logout_removes_login_device_for_current_session(): void
    {
        $user = $this->createUser();
        $loginResponse = $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'password-123',
        ]);
        $sessionId = $loginResponse->getSession()->getId();
        $cookie = $loginResponse->getCookie(config('session.cookie'));
        $this->assertDatabaseHas('login_devices', [
            'id_user' => $user->id,
            'session_id' => $sessionId,
        ]);

        $this->withCookie(config('session.cookie'), $cookie->getValue())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('login_devices', [
            'id_user' => $user->id,
            'session_id' => $sessionId,
        ]);
    }

    public function test_login_request_trims_username_before_lookup(): void
    {
        $user = $this->createUser('user');

        $this->post(route('login.process'), [
            'username' => '  ' . $user->username . '  ',
            'password' => 'password-123',
        ])->assertRedirect(route('dashboard_user'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_missing_username_fails_validation_without_throwing_in_prepare_hook(): void
    {
        $this->post(route('login.process'), [
            'password' => 'password-123',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_invalid_password_fails_login_validation(): void
    {
        $user = $this->createUser();

        $this->from(route('login'))
            ->post(route('login.process'), [
                'username' => $user->username,
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    #[DataProvider('invalidLoginPayloads')]
    public function test_auth_request_rejects_invalid_fields(
        array $payload,
        string $field
    ): void {
        $response = $this->from(route('login'))
            ->post(route('login.process'), $payload);

        $response->assertSessionHasErrors($field);
        $this->assertGuest();
    }

    public static function invalidLoginPayloads(): iterable
    {
        yield 'username exceeds 50 characters' => [
            ['username' => str_repeat('u', 51), 'password' => 'password-123'],
            'username',
        ];
        yield 'password is not a string' => [
            ['username' => 'admin', 'password' => ['password-123']],
            'password',
        ];
        yield 'password is shorter than 8 characters' => [
            ['username' => 'admin', 'password' => 'short'],
            'password',
        ];
        yield 'password exceeds 255 characters' => [
            ['username' => 'admin', 'password' => str_repeat('p', 256)],
            'password',
        ];
        yield 'remember is not accepted' => [
            ['username' => 'admin', 'password' => 'password-123', 'remember' => 'off'],
            'remember',
        ];
    }

    public function test_non_string_username_exposes_limiter_type_error_before_validation(): void
    {
        $response = $this->post(route('login.process'), [
            'username' => ['admin'],
            'password' => 'password-123',
        ]);

        $response->assertInternalServerError();
        $this->assertInstanceOf(
            \TypeError::class,
            $response->exceptions->last()
        );
        $this->assertStringContainsString(
            'mb_strtolower(): Argument #1 ($string) must be of type string, array given',
            $response->exceptions->last()->getMessage()
        );
    }

    public function test_login_rejects_unknown_username(): void
    {
        $response = $this->from(route('login'))
            ->post(route('login.process'), [
                'username' => 'missing-user',
                'password' => 'password-123',
            ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_rejects_user_without_department_code(): void
    {
        Department::firstOrCreate(['kode' => 'AUTH-01'], [
            'nama' => 'Unit Autentikasi',
            'status' => true,
        ]);
        $user = User::create([
            'username' => 'without-unit',
            'email' => 'without-unit@example.test',
            'nama' => 'Pengguna Tanpa Unit',
            'role' => 'admin',
            'kode_unit_kerja' => null,
            'password' => bcrypt('password-123'),
        ]);

        $this->from(route('login'))
            ->post(route('login.process'), [
                'username' => $user->username,
                'password' => 'password-123',
            ])
            ->assertSessionHasErrors('username');
    }

    public function test_login_rejects_2fa_account_without_email(): void
    {
        $user = $this->createUser('admin', true, null);

        $this->from(route('login'))
            ->post(route('login.process'), [
                'username' => $user->username,
                'password' => 'password-123',
            ])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[DataProvider('twoFactorDashboardRoles')]
    public function test_two_factor_login_sends_otp_and_completes_after_valid_code(
        string $role,
        string $dashboard
    ): void
    {
        Mail::fake();
        $user = $this->createUser($role, true);

        $loginResponse = $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'password-123',
        ]);
        $loginResponse->assertRedirect(route('auth.two-factor.challenge'));

        $this->assertGuest();
        $this->assertSame(
            $user->id,
            $loginResponse->getSession()->get('sigap_2fa_user_id')
        );
        Mail::assertSent(EmailOtpMail::class);
        $mail = Mail::sent(EmailOtpMail::class)->first();
        $sessionId = $loginResponse->getSession()->getId();
        $cacheKey = 'sigap_email_2fa_' . hash(
            'sha256',
            $user->id . '|' . $sessionId
        );
        $this->assertIsArray(Cache::get($cacheKey));

        $sessionCookie = $loginResponse->getCookie(config('session.cookie'));
        $response = $this->withCookie(
            config('session.cookie'),
            $sessionCookie->getValue()
        )->post(route('auth.two-factor.verify'), [
            'code' => $mail->code,
        ]);
        $this->assertNotSame($sessionId, $response->getSession()->getId());
        $this->assertSame(
            route($dashboard),
            $response->headers->get('Location'),
            json_encode($response->getSession()->get('errors'))
        );

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('login_devices', [
            'id_user' => $user->id,
        ]);
        Mail::assertSent(EmailOtpMail::class, 1);
    }

    public static function twoFactorDashboardRoles(): iterable
    {
        yield 'super admin' => ['super_admin', 'dashboard'];
        yield 'admin' => ['admin', 'dashboard'];
        yield 'user' => ['user', 'dashboard_user'];
    }

    public function test_two_factor_login_preserves_remember_choice_until_verification(): void
    {
        Mail::fake();
        $user = $this->createUser('admin', true);
        $loginResponse = $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'password-123',
            'remember' => 'yes',
        ]);
        $mail = Mail::sent(EmailOtpMail::class)->first();
        $cookie = $loginResponse->getCookie(config('session.cookie'));

        $response = $this->withCookie(
            config('session.cookie'),
            $cookie->getValue()
        )->post(route('auth.two-factor.verify'), [
            'code' => $mail->code,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'remember_token' => null,
        ]);
    }

    public function test_two_factor_login_with_email_delivery_failure_returns_validation_error(): void
    {
        $user = $this->createUser('admin', true);
        Mail::shouldReceive('to')
            ->once()
            ->with($user->email)
            ->andThrow(new \RuntimeException('Mail transport unavailable.'));

        $response = $this->from(route('login'))
            ->post(route('login.process'), [
                'username' => $user->username,
                'password' => 'password-123',
            ]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertFalse(
            $response->getSession()->has('sigap_2fa_user_id')
        );
    }

    public function test_invalid_otp_format_fails_before_challenge_lookup(): void
    {
        $user = $this->createUser('admin', true);

        $this->withSession([
            'sigap_2fa_user_id' => $user->id,
            'sigap_2fa_method' => 'email',
        ])->from(route('auth.two-factor.challenge'))
            ->post(route('auth.two-factor.verify'), ['code' => '123'])
            ->assertSessionHasErrors('code');
    }

    public function test_challenge_page_requires_an_email_challenge_session(): void
    {
        $this->get(route('auth.two-factor.challenge'))
            ->assertRedirect(route('login'));

        $user = $this->createUser('admin', true);
        $this->withSession([
            'sigap_2fa_user_id' => $user->id,
            'sigap_2fa_method' => 'email',
        ])->get(route('auth.two-factor.challenge'))
            ->assertOk();
    }

    public function test_missing_or_invalid_challenge_session_redirects_to_login(): void
    {
        $this->post(route('auth.two-factor.verify'), ['code' => '123456'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');
    }

    public function test_resend_without_valid_challenge_redirects_to_login(): void
    {
        $this->post(route('auth.two-factor.resend'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');
    }
}

<?php

namespace Tests\Unit\Services;

use App\Mail\EmailOtpMail;
use App\Helpers\DeviceHelper;
use App\Models\Department;
use App\Models\LoginDevice;
use App\Models\User;
use App\Repositories\AuthRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use RuntimeException;
use Tests\TestCase;

class AuthServiceDetailedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $session = app('session')->driver();
        $session->start();
        request()->setLaravelSession($session);
    }

    private function newUser(array $attributes = []): User
    {
        $user = new User(array_merge([
            'username' => 'otp-user',
            'email' => 'otp@example.test',
            'nama' => 'User OTP',
            'email_2fa_enabled' => true,
            'status' => true,
            'password' => Hash::make('password-123'),
        ], $attributes));
        $user->id = $attributes['id'] ?? 101;

        return $user;
    }

    private function service(
        ?User $loginUser = null,
        ?UserRepository $userRepository = null,
        ?AuthRepository $authRepository = null
    ): AuthService {
        $userRepository ??= Mockery::mock(UserRepository::class);
        $authRepository ??= Mockery::mock(AuthRepository::class);

        if ($loginUser !== null) {
            $userRepository->shouldReceive('getByUsername')
                ->once()
                ->with($loginUser->username)
                ->andReturn($loginUser);
        }

        return new AuthService($userRepository, $authRepository);
    }

    public function test_send_otp_rejects_missing_email_and_disabled_two_factor(): void
    {
        $service = $this->service();

        foreach ([
            $this->newUser(['email' => null]),
            $this->newUser(['email_2fa_enabled' => false]),
        ] as $user) {
            try {
                $service->sendEmailOtp($user);
                $this->fail('OTP configuration should have been rejected.');
            } catch (ValidationException $exception) {
                $this->assertSame(
                    'Email atau konfigurasi 2FA tidak valid.',
                    $exception->errors()['code'][0]
                );
            }
        }

    }

    public function test_login_rejects_a_missing_department_relation(): void
    {
        $user = $this->newUser(['kode_unit_kerja' => 'MISSING-UNIT']);
        $user->setRelation('department', null);
        $service = $this->service(loginUser: $user);

        try {
            $service->login([
                'username' => $user->username,
                'password' => 'password-123',
            ]);
            $this->fail('A user without a department relation should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Unit Kerja akun tidak valid.',
                $exception->errors()['username'][0]
            );
        }
    }

    public function test_send_otp_rejects_request_during_resend_cooldown(): void
    {
        $this->travelTo(Carbon::parse('2026-05-01 10:00:00'));
        Mail::fake();
        $service = $this->service();
        $user = $this->newUser();
        $resendKey = $service->otpResendKey($user);
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->with($resendKey, 1)
            ->andReturnTrue();
        RateLimiter::shouldReceive('availableIn')
            ->once()
            ->with($resendKey)
            ->andReturn(60);

        try {
            $service->sendEmailOtp($user);
            $this->fail('A resend request during cooldown should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Silakan tunggu 60 detik sebelum meminta kode baru.',
                $exception->errors()['code'][0]
            );
        }

        Mail::assertNothingSent();
    }

    public function test_send_otp_sends_and_stores_a_hashed_challenge(): void
    {
        $now = Carbon::parse('2026-05-01 10:00:00');
        $this->travelTo($now);
        Mail::fake();
        $service = $this->service();
        $user = $this->newUser();

        $service->sendEmailOtp($user);

        Mail::assertSent(EmailOtpMail::class, function (EmailOtpMail $mail) use ($user, $now): bool {
            $this->assertSame($user->email, $mail->to[0]['address']);
            $this->assertSame($user->nama, $mail->nama);
            $this->assertMatchesRegularExpression('/^\d{6}$/', $mail->code);

            $challenge = Cache::get(app(AuthService::class)->otpCacheKey($user));

            return is_array($challenge)
                && Hash::check($mail->code, $challenge['hash'])
                && $challenge['attempts'] === 0
                && $challenge['expires_at'] === $now->copy()->addMinutes(2)->timestamp;
        });
        $resendKey = $service->otpResendKey($user);
        $this->assertSame(1, RateLimiter::attempts($resendKey));
        $this->assertTrue(RateLimiter::tooManyAttempts($resendKey, 1));
        $this->assertSame(60, RateLimiter::availableIn($resendKey));
        $this->assertSame(
            $now->copy()->addSeconds(60)->timestamp,
            session()->get('sigap_2fa_resend_at')
        );
        $this->assertSame(60, $service->emailOtpResendWait());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_send_otp_uses_the_expected_random_int_bounds(): void
    {
        eval(<<<'PHP'
namespace App\Services;
function random_int(int $min, int $max): int
{
    $GLOBALS['auth_service_random_int_arguments'] = [$min, $max];

    return 123456;
}
PHP);

        $GLOBALS['auth_service_random_int_arguments'] = null;
        Mail::fake();
        $service = $this->service();
        $user = $this->newUser();

        $service->sendEmailOtp($user);

        $this->assertSame([100000, 999999], $GLOBALS['auth_service_random_int_arguments']);
        Mail::assertSent(
            EmailOtpMail::class,
            fn (EmailOtpMail $mail): bool => $mail->code === '123456'
                && preg_match('/^\d{6}$/', $mail->code) === 1
        );

        $challenge = Cache::get($service->otpCacheKey($user));
        $this->assertIsArray($challenge);
        $this->assertTrue(Hash::check('123456', $challenge['hash']));
    }

    public function test_send_otp_uses_username_when_display_name_is_null(): void
    {
        Mail::fake();
        $service = $this->service();
        $user = $this->newUser(['nama' => null]);

        $service->sendEmailOtp($user);

        Mail::assertSent(
            EmailOtpMail::class,
            fn (EmailOtpMail $mail): bool => $mail->nama === $user->username
        );
    }

    public function test_send_otp_converts_mail_exception_to_validation_error(): void
    {
        $service = $this->service();
        $user = $this->newUser();
        $mailException = new RuntimeException('SMTP unavailable');
        $exceptionHandler = Mockery::mock(ExceptionHandler::class);
        $exceptionHandler->shouldReceive('report')
            ->once()
            ->with($mailException);
        $this->app->instance(ExceptionHandler::class, $exceptionHandler);
        Mail::shouldReceive('to')
            ->once()
            ->with($user->email)
            ->andThrow($mailException);

        try {
            $service->sendEmailOtp($user);
            $this->fail('Mail failure should be converted to validation error.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Kode OTP gagal dikirim. Silakan coba kembali.',
                $exception->errors()['code'][0]
            );
        }

        $this->assertNull(Cache::get($service->otpCacheKey($user)));
        $this->assertSame(0, RateLimiter::attempts($service->otpResendKey($user)));
        $this->assertFalse(session()->has('sigap_2fa_resend_at'));
    }

    public function test_otp_keys_are_distinct_and_resend_wait_never_negative(): void
    {
        $service = $this->service();
        $user = $this->newUser();
        $originalSession = request()->session();
        $sessionId = $originalSession->getId();
        $expectedHash = hash('sha256', $user->id . '|' . $sessionId);

        $this->assertSame('sigap_email_2fa_' . $expectedHash, $service->otpCacheKey($user));
        $this->assertSame('sigap_email_2fa_resend_' . $expectedHash, $service->otpResendKey($user));
        $this->assertNotSame($service->otpCacheKey($user), $service->otpResendKey($user));
        $this->assertNotSame($service->otpCacheKey($user), $service->otpCacheKey($this->newUser(['id' => 102])));

        $otherSession = new Store(
            'test',
            new ArraySessionHandler(120),
            'auth-session-two'
        );
        $otherSession->start();
        request()->setLaravelSession($otherSession);
        $this->assertNotSame(
            'sigap_email_2fa_' . $expectedHash,
            $service->otpCacheKey($user)
        );
        request()->setLaravelSession($originalSession);
        $this->assertSame(0, $service->emailOtpResendWait());

        session(['sigap_2fa_resend_at' => now()->timestamp]);
        $this->assertSame(0, $service->emailOtpResendWait());

        session(['sigap_2fa_resend_at' => now()->addSeconds(25)->timestamp]);
        $this->assertSame(25, $service->emailOtpResendWait());

        session(['sigap_2fa_resend_at' => now()->subSecond()->timestamp]);
        $this->assertSame(0, $service->emailOtpResendWait());

        session(['sigap_2fa_resend_at' => 'invalid']);
        $this->assertSame(0, $service->emailOtpResendWait());
    }

    public function test_resend_wait_uses_zero_default_for_a_missing_session_value(): void
    {
        $this->travelTo(Carbon::createFromTimestamp(-1));
        Cache::flush();
        $service = $this->service();
        session()->forget('sigap_2fa_resend_at');

        $this->assertFalse(session()->has('sigap_2fa_resend_at'));
        $this->assertSame(1, $service->emailOtpResendWait());

        $resendKey = $service->otpResendKey($this->newUser());
        RateLimiter::clear($resendKey);
        $this->assertSame(1, $service->emailOtpResendWait());
        RateLimiter::hit($resendKey, 60);
        $this->assertSame(1, $service->emailOtpResendWait());
    }

    public function test_clear_challenge_removes_cache_when_session_user_exists(): void
    {
        $user = $this->newUser();
        $authRepository = Mockery::mock(AuthRepository::class);
        $authRepository->shouldReceive('getUserById')
            ->once()
            ->with($user->id)
            ->andReturn($user);
        $service = $this->service(authRepository: $authRepository);
        session([
            'sigap_2fa_user_id' => $user->id,
            'sigap_2fa_remember' => true,
            'sigap_2fa_method' => 'email',
            'sigap_2fa_resend_at' => now()->timestamp,
        ]);
        $cacheKey = $service->otpCacheKey($user);
        Cache::put($cacheKey, ['hash' => 'cached'], now()->addMinute());

        $service->clearTwoFactorChallenge();

        $this->assertNull(Cache::get($cacheKey));
        foreach ([
            'sigap_2fa_user_id',
            'sigap_2fa_remember',
            'sigap_2fa_method',
            'sigap_2fa_resend_at',
        ] as $key) {
            $this->assertFalse(session()->has($key));
        }
    }

    public function test_clear_challenge_handles_absent_session_user_and_missing_user_record(): void
    {
        $authRepository = Mockery::mock(AuthRepository::class);
        $authRepository->shouldReceive('getUserById')
            ->once()
            ->with(999)
            ->andReturnNull();
        $service = $this->service(authRepository: $authRepository);

        $service->clearTwoFactorChallenge();
        session(['sigap_2fa_user_id' => 999]);
        $service->clearTwoFactorChallenge();

        $this->assertFalse(session()->has('sigap_2fa_user_id'));
    }

    public function test_login_rejects_inactive_user_before_device_validation(): void
    {
        $user = $this->newUser(['status' => false]);
        $authRepository = Mockery::mock(AuthRepository::class);
        $authRepository->shouldNotReceive('getDeviceByFingerprint');
        $service = $this->service(
            loginUser: $user,
            authRepository: $authRepository
        );

        try {
            $service->login([
                'username' => $user->username,
                'password' => 'password-123',
            ]);
            $this->fail('Inactive users should not be able to log in.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Akun Anda sedang nonaktif. Silakan hubungi administrator.',
                $exception->errors()['username'][0]
            );
        }
    }

    public function test_login_checks_device_limit_before_starting_two_factor_challenge(): void
    {
        Mail::fake();
        $user = $this->newUser(['kode_unit_kerja' => 'AUTH-01']);
        $user->setRelation('department', new Department());

        $authRepository = Mockery::mock(AuthRepository::class);
        $authRepository->shouldReceive('getDeviceByFingerprint')
            ->once()
            ->with($user->id, DeviceHelper::fingerprint())
            ->andReturnNull();
        $authRepository->shouldReceive('countUserDevices')
            ->once()
            ->with($user->id)
            ->andReturn(2);
        $service = $this->service(
            loginUser: $user,
            authRepository: $authRepository
        );

        try {
            $service->login([
                'username' => $user->username,
                'password' => 'password-123',
            ]);
            $this->fail('A new device should be rejected at the two-device limit.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Akun ini sudah digunakan pada maksimal 2 perangkat.',
                $exception->errors()['username'][0]
            );
        }

        Mail::assertNothingSent();
    }

    public function test_complete_login_uses_fresh_active_status_and_tracks_device(): void
    {
        $user = User::create([
            'username' => 'complete-login-active',
            'email' => 'complete-login@example.test',
            'role' => 'admin',
            'password' => Hash::make('password-123'),
            'status' => true,
        ]);
        request()->headers->set('User-Agent', 'AuthService test browser');
        request()->headers->set('Accept-Language', 'id-ID');
        request()->headers->set('Sec-CH-UA', '"Browser"');
        request()->headers->set('Sec-CH-UA-Platform', '"Windows"');
        request()->server->set('REMOTE_ADDR', '192.0.2.25');

        $fingerprint = DeviceHelper::fingerprint();
        $authRepository = Mockery::mock(AuthRepository::class);
        $authRepository->shouldReceive('getDeviceByFingerprint')
            ->once()
            ->with($user->id, $fingerprint)
            ->andReturnNull();
        $authRepository->shouldReceive('countUserDevices')
            ->once()
            ->with($user->id)
            ->andReturn(1);
        $authRepository->shouldReceive('updateOrCreateDevice')
            ->once()
            ->with(
                $user->id,
                $fingerprint,
                Mockery::on(function (array $data): bool {
                    return $data['nama_perangkat'] === null
                        && $data['browser'] === request()->userAgent()
                        && $data['platform'] === null
                        && $data['ip_address'] === request()->ip()
                        && $data['session_id'] === request()->session()->getId()
                        && $data['aktivitas_terakhir'] instanceof Carbon;
                })
            )
            ->andReturn(new LoginDevice());
        Auth::shouldReceive('login')
            ->once()
            ->with($user, false);

        $oldSessionId = session()->getId();
        $service = $this->service(authRepository: $authRepository);

        $result = $service->completeLogin($user);

        $this->assertSame($user->id, $result->id);
        $this->assertNotSame($oldSessionId, session()->getId());
    }

    public function test_complete_login_rejects_user_deactivated_after_lookup(): void
    {
        $user = User::create([
            'username' => 'complete-login-inactive',
            'email' => 'complete-login-inactive@example.test',
            'role' => 'admin',
            'password' => Hash::make('password-123'),
            'status' => true,
        ]);
        User::whereKey($user->id)->update(['status' => false]);

        $authRepository = Mockery::mock(AuthRepository::class);
        $authRepository->shouldReceive('getUserById')
            ->once()
            ->with($user->id)
            ->andReturn($user);
        $authRepository->shouldNotReceive('getDeviceByFingerprint');
        Auth::shouldReceive('login')->never();

        $service = $this->service(authRepository: $authRepository);
        session([
            'sigap_2fa_user_id' => $user->id,
            'sigap_2fa_remember' => true,
            'sigap_2fa_method' => 'email',
            'sigap_2fa_resend_at' => now()->timestamp,
        ]);
        $cacheKey = $service->otpCacheKey($user);
        Cache::put($cacheKey, ['hash' => 'cached'], now()->addMinute());

        try {
            $service->completeLogin($user, true);
            $this->fail('A user deactivated after lookup should not complete login.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Akun Anda sedang nonaktif. Silakan hubungi administrator.',
                $exception->errors()['username'][0]
            );
        }

        $this->assertTrue((bool) $user->status);
        $this->assertNull(Cache::get($cacheKey));
        foreach ([
            'sigap_2fa_user_id',
            'sigap_2fa_remember',
            'sigap_2fa_method',
            'sigap_2fa_resend_at',
        ] as $key) {
            $this->assertFalse(session()->has($key));
        }
    }

    public function test_logout_deletes_device_and_invalidates_session(): void
    {
        $sessionId = session()->getId();
        $oldToken = session()->token();
        session(['temporary_challenge' => true]);

        $authRepository = Mockery::mock(AuthRepository::class);
        $authRepository->shouldReceive('deleteDeviceBySession')
            ->once()
            ->with($sessionId)
            ->andReturn(1);
        Auth::shouldReceive('logout')->once();

        $this->service(authRepository: $authRepository)->logout();

        $this->assertFalse(session()->has('temporary_challenge'));
        $this->assertNotSame($sessionId, session()->getId());
        $newToken = session()->token();
        $this->assertIsString($newToken);
        $this->assertNotSame('', $newToken);
        $this->assertNotSame($oldToken, $newToken);
    }
}

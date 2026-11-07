<?php

namespace Tests\Unit\Services;

use App\Mail\EmailOtpMail;
use App\Models\User;
use App\Repositories\AuthRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AuthServiceDetailedTest extends TestCase
{
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
            'password' => Hash::make('password-123'),
        ], $attributes));
        $user->id = 101;

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
        $service = $this->service();
        $user = $this->newUser();
        RateLimiter::hit($service->otpResendKey($user), 60);

        $this->expectException(ValidationException::class);
        $service->sendEmailOtp($user);
    }

    public function test_send_otp_sends_and_stores_a_hashed_challenge(): void
    {
        Mail::fake();
        $service = $this->service();
        $user = $this->newUser();

        $service->sendEmailOtp($user);

        Mail::assertSent(EmailOtpMail::class, function (EmailOtpMail $mail) use ($user): bool {
            $this->assertSame($user->email, $mail->to[0]['address']);

            $challenge = Cache::get(app(AuthService::class)->otpCacheKey($user));

            return is_array($challenge)
                && Hash::check($mail->code, $challenge['hash'])
                && $challenge['attempts'] === 0
                && $challenge['expires_at'] > now()->timestamp;
        });
        $this->assertGreaterThan(0, $service->emailOtpResendWait());
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
        Mail::shouldReceive('to')
            ->once()
            ->with($user->email)
            ->andThrow(new RuntimeException('SMTP unavailable'));

        try {
            $service->sendEmailOtp($user);
            $this->fail('Mail failure should be converted to validation error.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Kode OTP gagal dikirim. Silakan coba kembali.',
                $exception->errors()['code'][0]
            );
        }
    }

    public function test_otp_keys_are_distinct_and_resend_wait_never_negative(): void
    {
        $service = $this->service();
        $user = $this->newUser();

        $this->assertNotSame(
            $service->otpCacheKey($user),
            $service->otpResendKey($user)
        );
        $this->assertSame(0, $service->emailOtpResendWait());

        session(['sigap_2fa_resend_at' => now()->addSeconds(25)->timestamp]);
        $this->assertGreaterThan(0, $service->emailOtpResendWait());

        session(['sigap_2fa_resend_at' => now()->subSecond()->timestamp]);
        $this->assertSame(0, $service->emailOtpResendWait());
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
            'sigap_2fa_method' => 'email',
        ]);
        $cacheKey = $service->otpCacheKey($user);
        Cache::put($cacheKey, ['hash' => 'cached'], now()->addMinute());

        $service->clearTwoFactorChallenge();

        $this->assertNull(Cache::get($cacheKey));
        $this->assertFalse(session()->has('sigap_2fa_user_id'));
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
}

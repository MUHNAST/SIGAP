<?php

namespace Tests\Feature;

use App\Mail\EmailOtpMail;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use ErrorException;
use Tests\TestCase;

class TwoFactorAttemptTest extends TestCase
{
    use RefreshDatabase;

    private function createTwoFactorUser(
        string $role = 'admin',
        bool $enabled = true,
        ?string $email = 'otp@example.test'
    ): User {
        Department::firstOrCreate(['kode' => 'OTP-01'], [
            'nama' => 'Unit OTP',
            'status' => true,
        ]);

        return User::create([
            'username' => 'otp-user-' . uniqid(),
            'email' => $email,
            'nama' => 'Pengguna OTP',
            'role' => $role,
            'kode_unit_kerja' => 'OTP-01',
            'email_2fa_enabled' => $enabled,
            'password' => bcrypt('password-123'),
        ]);
    }

    /**
     * @return array{user: User, cookie: string, sessionId: string, cacheKey: string}
     */
    private function beginChallenge(string $role = 'admin'): array
    {
        Mail::fake();
        $user = $this->createTwoFactorUser($role);
        $response = $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'password-123',
        ]);
        $response->assertRedirect(route('auth.two-factor.challenge'));

        return [
            'user' => $user,
            'cookie' => $response->getCookie(config('session.cookie'))->getValue(),
            'sessionId' => $response->getSession()->getId(),
            'cacheKey' => 'sigap_email_2fa_' . hash(
                'sha256',
                $user->id . '|' . $response->getSession()->getId()
            ),
        ];
    }

    private function verifyWithCookie(string $cookie, string $code)
    {
        return $this->from(route('auth.two-factor.challenge'))
            ->withCookie(config('session.cookie'), $cookie)
            ->post(route('auth.two-factor.verify'), ['code' => $code]);
    }

    public function test_wrong_code_increments_attempts_and_keeps_original_expiry(): void
    {
        $challenge = $this->beginChallenge();
        $expiresAt = now()->addMinute()->timestamp;
        Cache::put($challenge['cacheKey'], [
            'hash' => Hash::make('654321'),
            'attempts' => 1,
            'expires_at' => $expiresAt,
        ], now()->addMinutes(2));

        $response = $this->verifyWithCookie($challenge['cookie'], '123456');

        $response->assertSessionHasErrors('code');
        $stored = Cache::get($challenge['cacheKey']);
        $this->assertSame(2, $stored['attempts']);
        $this->assertSame($expiresAt, $stored['expires_at']);
    }

    public function test_wrong_code_on_fifth_attempt_clears_challenge(): void
    {
        $challenge = $this->beginChallenge();
        Cache::put($challenge['cacheKey'], [
            'hash' => Hash::make('654321'),
            'attempts' => 4,
            'expires_at' => now()->addMinute()->timestamp,
        ], now()->addMinutes(2));

        $response = $this->verifyWithCookie($challenge['cookie'], '123456');

        $response->assertRedirect(route('login'));
        $this->assertNull(Cache::get($challenge['cacheKey']));
        $this->assertFalse(
            $response->getSession()->has('sigap_2fa_user_id')
        );
    }

    public function test_challenge_already_at_five_attempts_is_rejected(): void
    {
        $challenge = $this->beginChallenge();
        Cache::put($challenge['cacheKey'], [
            'hash' => Hash::make('654321'),
            'attempts' => 5,
            'expires_at' => now()->addMinute()->timestamp,
        ], now()->addMinutes(2));

        $response = $this->verifyWithCookie($challenge['cookie'], '123456');

        $response->assertRedirect(route('login'));
        $this->assertNull(Cache::get($challenge['cacheKey']));
    }

    public function test_wrong_code_with_one_second_remaining_keeps_cache(): void
    {
        $challenge = $this->beginChallenge();
        $this->travelTo(now());
        $expiresAt = now()->addSecond()->timestamp;
        Cache::put($challenge['cacheKey'], [
            'hash' => Hash::make('654321'),
            'attempts' => 0,
            'expires_at' => $expiresAt,
        ], now()->addMinute());

        $response = $this->verifyWithCookie($challenge['cookie'], '123456');

        $response->assertSessionHasErrors('code');
        $this->assertSame(1, Cache::get($challenge['cacheKey'])['attempts']);
        $this->assertSame($expiresAt, Cache::get($challenge['cacheKey'])['expires_at']);
    }

    #[DataProvider('malformedChallenges')]
    public function test_malformed_or_expired_challenge_is_removed(string $case): void
    {
        $challenge = $this->beginChallenge();
        $value = match ($case) {
            'not-array' => 'invalid-cache',
            'missing-hash' => [
                'attempts' => 0,
                'expires_at' => now()->addMinute()->timestamp,
            ],
            'missing-expiry' => [
                'hash' => Hash::make('123456'),
                'attempts' => 0,
            ],
            'expires-now' => [
                'hash' => Hash::make('123456'),
                'attempts' => 0,
                'expires_at' => now()->timestamp,
            ],
        };
        Cache::put($challenge['cacheKey'], $value, now()->addMinute());

        $response = $this->verifyWithCookie($challenge['cookie'], '123456');

        $response->assertSessionHasErrors('code');
        $this->assertNull(Cache::get($challenge['cacheKey']));
    }

    public static function malformedChallenges(): iterable
    {
        yield 'cache is not an array' => ['not-array'];
        yield 'hash is missing' => ['missing-hash'];
        yield 'expiry is missing' => ['missing-expiry'];
        yield 'expiry reached now' => ['expires-now'];
    }

    public function test_challenge_without_attempts_key_records_php_runtime_behavior(): void
    {
        $challenge = $this->beginChallenge();
        Cache::put($challenge['cacheKey'], [
            'hash' => Hash::make('654321'),
            'expires_at' => now()->addMinute()->timestamp,
        ], now()->addMinutes(2));

        $response = $this->verifyWithCookie($challenge['cookie'], '123456');

        $response->assertInternalServerError();
        $this->assertInstanceOf(ErrorException::class, $response->exceptions->last());
        $this->assertStringContainsString(
            'Undefined array key "attempts"',
            $response->exceptions->last()->getMessage()
        );
    }

    public function test_user_with_disabled_two_factor_is_redirected_and_challenge_cleared(): void
    {
        Mail::fake();
        $user = $this->createTwoFactorUser(enabled: false);
        $response = $this->withSession([
            'sigap_2fa_user_id' => $user->id,
            'sigap_2fa_method' => 'email',
        ])->post(route('auth.two-factor.resend'));

        $response->assertRedirect(route('login'));
        $this->assertNotEmpty($response->getSession()->get('errors'));
        Mail::assertNothingSent();
    }

    public function test_verify_rejects_challenge_for_user_without_email(): void
    {
        $challenge = $this->beginChallenge();
        $challenge['user']->forceFill(['email' => null])->save();

        $response = $this->verifyWithCookie($challenge['cookie'], '123456');

        $response->assertRedirect(route('login'));
        $this->assertFalse(
            $response->getSession()->has('sigap_2fa_user_id')
        );
        $this->assertNull(Cache::get($challenge['cacheKey']));
    }

    public function test_verify_rejects_challenge_when_user_was_deleted(): void
    {
        $challenge = $this->beginChallenge();
        $challenge['user']->delete();

        $response = $this->verifyWithCookie($challenge['cookie'], '123456');

        $response->assertRedirect(route('login'));
        $this->assertFalse(
            $response->getSession()->has('sigap_2fa_user_id')
        );
    }

    public function test_resend_rejects_challenge_when_user_was_deleted(): void
    {
        $challenge = $this->beginChallenge();
        $challenge['user']->delete();

        $response = $this->from(route('auth.two-factor.challenge'))
            ->withCookie(config('session.cookie'), $challenge['cookie'])
            ->post(route('auth.two-factor.resend'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(
            $response->getSession()->has('sigap_2fa_user_id')
        );
        Mail::assertSent(EmailOtpMail::class, 1);
    }

    public function test_successful_otp_login_rejects_unsupported_role(): void
    {
        $challenge = $this->beginChallenge('unknown-role');
        $message = Mail::sent(EmailOtpMail::class)->first();

        $response = $this->verifyWithCookie(
            $challenge['cookie'],
            $message->code
        );

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNotEmpty($response->getSession()->get('errors'));
    }

    public function test_resend_sends_otp_for_valid_challenge(): void
    {
        Mail::fake();
        $user = $this->createTwoFactorUser();
        $response = $this->withSession([
            'sigap_2fa_user_id' => $user->id,
            'sigap_2fa_method' => 'email',
        ])->post(route('auth.two-factor.resend'));

        $response->assertRedirect(route('auth.two-factor.challenge'));
        Mail::assertSent(EmailOtpMail::class);
    }

    public function test_resend_mail_failure_is_not_reported_as_success(): void
    {
        $user = $this->createTwoFactorUser();
        Mail::shouldReceive('to')
            ->once()
            ->with($user->email)
            ->andThrow(new \RuntimeException('Mail transport unavailable.'));

        $response = $this->from(route('auth.two-factor.challenge'))
            ->withSession([
            'sigap_2fa_user_id' => $user->id,
            'sigap_2fa_method' => 'email',
        ])->post(route('auth.two-factor.resend'));

        $this->assertNotEmpty($response->getSession()->get('errors'));
    }
}

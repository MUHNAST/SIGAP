<?php

namespace Tests\Feature;

use App\Mail\EmailOtpMail;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\NotificationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginViewResponse;
use Mockery;
use Tests\TestCase;

class AuthenticationProviderTest extends TestCase
{
    public function test_testing_environment_uses_isolated_database_and_fake_backends(): void
    {
        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('session.driver'));
        $this->assertSame('array', config('mail.default'));
        $this->assertSame('sync', config('queue.default'));
    }

    public function test_custom_email_two_factor_remains_while_unintegrated_fortify_features_are_disabled(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->assertFalse(Route::has('two-factor.login'));
        $this->assertFalse(Route::has('two-factor.enable'));
        $this->assertFalse(Route::has('passkey.store'));

        $this->assertTrue(Route::has('auth.two-factor.challenge'));
        $this->assertTrue(Route::has('setting.two-factor.enable'));
    }

    public function test_fortify_login_limiter_is_the_effective_registered_limiter(): void
    {
        $request = Request::create('/login/process', 'POST', [
            Fortify::username() => 'Ádmin.User',
        ], [], [], ['REMOTE_ADDR' => '192.0.2.10']);

        $limit = RateLimiter::limiter('login')($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertSame('admin.user|192.0.2.10', $limit->key);
        $this->assertSame(5, $limit->maxAttempts);
        $this->assertSame(60, $limit->decaySeconds);
    }

    public function test_app_provider_login_limiter_callback_is_exercised(): void
    {
        $loginLimiter = null;
        RateLimiter::shouldReceive('for')
            ->once()
            ->with('login', Mockery::on(function ($callback) use (&$loginLimiter): bool {
                if (! $callback instanceof \Closure) {
                    return false;
                }

                $loginLimiter = $callback;

                return true;
            }));
        \Illuminate\Support\Facades\View::shouldReceive('composer')
            ->once()
            ->with('*', Mockery::type(\Closure::class));

        (new AppServiceProvider($this->app))->boot();

        $limit = $loginLimiter(Request::create(
            '/login/process',
            'POST',
            ['username' => 'provider-user'],
            [],
            [],
            ['REMOTE_ADDR' => '192.0.2.13']
        ));

        $this->assertSame('192.0.2.13|provider-user', $limit->key);
        $this->assertSame(5, $limit->maxAttempts);
    }

    public function test_two_factor_limiter_uses_session_login_id(): void
    {
        $this->withSession(['login.id' => 'two-factor-user']);
        $request = Request::create('/two-factor');
        $request->setLaravelSession(app('session')->driver());

        $limit = RateLimiter::limiter('two-factor')($request);

        $this->assertSame('two-factor-user', $limit->key);
        $this->assertSame(5, $limit->maxAttempts);
    }

    public function test_fortify_login_view_callback_returns_the_authentication_view(): void
    {
        $response = app(LoginViewResponse::class)
            ->toResponse(Request::create('/login'));

        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $response);
        $this->assertSame('auth.index', $response->name());
    }

    public function test_passkey_limiter_uses_credential_id_or_session_fallback(): void
    {
        $credentialRequest = Request::create('/passkeys', 'POST', [
            'credential' => ['id' => 'credential-123'],
        ], [], [], ['REMOTE_ADDR' => '192.0.2.11']);
        $credentialLimit = RateLimiter::limiter('passkeys')($credentialRequest);

        $this->assertSame(
            'credential-123|192.0.2.11',
            $credentialLimit->key
        );
        $this->assertSame(10, $credentialLimit->maxAttempts);

        $session = app('session')->driver();
        $session->setId('session-fallback');
        $session->start();
        $sessionRequest = Request::create(
            '/passkeys',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => '192.0.2.12']
        );
        $sessionRequest->setLaravelSession($session);
        $sessionLimit = RateLimiter::limiter('passkeys')($sessionRequest);

        $this->assertSame(
            $session->getId() . '|192.0.2.12',
            $sessionLimit->key
        );
    }

    public function test_email_otp_mailable_declares_its_current_subject_and_view(): void
    {
        $mailable = new EmailOtpMail('123456', 'Pengguna');

        $this->assertSame(
            'Pengiriman Kode OTP Lewat Email',
            $mailable->envelope()->subject
        );
        $this->assertSame(
            'notification.email-otp',
            $mailable->content()->view
        );
        $this->assertSame([], $mailable->attachments());

        $mailable->build();
        $this->assertSame('Kode Verifikasi Login SIGAP', $mailable->subject);
    }

    public function test_guest_view_composer_shares_empty_notification_data(): void
    {
        $view = view('auth.index', [
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ]);
        $view->render();

        $this->assertSame([], $view->getData()['topNotifications']->all());
        $this->assertSame(0, $view->getData()['unreadCount']);
    }

    public function test_authenticated_view_composer_shares_notification_data(): void
    {
        $user = new User(['role' => 'admin']);
        $this->actingAs($user);

        $notifications = Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('getStatistics')
            ->once()
            ->andReturn(['belumDibaca' => 2]);
        $notifications->shouldReceive('getLatestNotifications')
            ->once()
            ->with(3)
            ->andReturn(new Collection());
        $this->app->instance(NotificationService::class, $notifications);

        $view = view('auth.index', [
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ]);
        $view->render();

        $this->assertSame(2, $view->getData()['unreadCount']);
        $this->assertInstanceOf(Collection::class, $view->getData()['topNotifications']);
    }
}

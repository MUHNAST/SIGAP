<?php
namespace App\Providers;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->ip().'|'.$request->input('username')
            );
        });
        View::composer('*', function ($view) {
            if (!Auth::check()) {
                $view->with([
                    'topNotifications' => collect(),
                    'unreadCount' => 0,
                ]);
                return;
            }
            $service = app(NotificationService::class);
            $statistics = $service->getStatistics();
            $view->with([
                'topNotifications' => $service->getLatestNotifications(3),
                'unreadCount' => $statistics['belumDibaca'],
            ]);
        });
    }
}

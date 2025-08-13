<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     */
   public const HOME = '/redirect-role';


    /**
     * Redirect ke dashboard sesuai role user.
     */
    public static function redirectTo()
    {
        switch (auth()->user()->role) {
            case 'admin':
                return '/admin/dashboard';
            case 'guru':
                return '/guru/dashboard';
            case 'tu':
                return '/tu/dashboard';
            case 'piket':
                return '/piket/dashboard';
            case 'kepsek':
                return '/kepsek/dashboard';
            default:
                return '/login';
        }
    }

    /**
     * Konfigurasi routes aplikasi.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}

<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for('api-employees', fn (Request $request): Limit => Limit::perMinute((int) config('api.rate_limits.employees'))
            ->by('employees|'.hash('sha256', (string) $request->header('X-Client-Key')).'|'.$request->ip()));

        RateLimiter::for('api-visits', fn (Request $request): Limit => Limit::perMinute((int) config('api.rate_limits.visits'))
            ->by('visits|'.hash('sha256', (string) $request->header('X-Client-Key')).'|'.$request->ip()));
    }
}

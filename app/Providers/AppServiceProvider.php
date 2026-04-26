<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ── Production safety ──────────────────────────────────────
        //
        // Force HTTPS for every URL the framework generates when we're
        // in production — protects login, password reset, and admin
        // panel from being served over plain HTTP if someone misroutes
        // a request through the load balancer.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            // Loud log warning if APP_DEBUG is left on in production —
            // shows in the first request's logs so a misconfigured
            // deploy is impossible to miss. (APP_DEBUG=true in prod
            // leaks env vars + stack traces with credentials.)
            if (config('app.debug') === true) {
                Log::critical('SECURITY: APP_DEBUG=true in production. Set APP_DEBUG=false immediately.');
            }
        }

        // ── Rate Limiters ──────────────────────────────────────────
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Stricter limit for auth endpoints (prevent brute force)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Stricter limit for contribution submissions
        RateLimiter::for('contributions', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // OTP sending limit
        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });
    }
}

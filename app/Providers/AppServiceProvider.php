<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
        // Disable resource wrapping for cleaner API responses
        // Returns {"id": 1, "title": "..."} instead of {"data": {"id": 1, ...}}
        JsonResource::withoutWrapping();

        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Login endpoint: 5 attempts per minute per IP
        // Prevents brute force password attacks
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Register endpoint: 3 attempts per minute per IP
        // Prevents spam account creation
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // General API: 60 requests per minute per user or IP
        // Standard API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}

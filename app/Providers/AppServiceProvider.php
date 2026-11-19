<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Storage\DocumentStorageService::class);
    }

    public function boot(): void
    {
        // Ensure laravel-exceptions-renderer view namespace is always loaded in console commands.
        // In Laravel 11, when APP_DEBUG=false, FoundationServiceProvider skips loading this namespace,
        // causing `php artisan optimize` / `view:cache` to crash with:
        // "Unable to locate a class or view for component [laravel-exceptions-renderer::syntax-highlight]".
        if ($this->app->runningInConsole()) {
            $rendererPath = base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/exceptions/renderer');
            if (is_dir($rendererPath)) {
                $this->loadViewsFrom($rendererPath, 'laravel-exceptions-renderer');
            }
        }

        // Force HTTPS in production or when running behind an SSL-terminating reverse proxy / load balancer
        if (app()->environment('production') || request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        \Laravel\Sanctum\Sanctum::usePersonalAccessTokenModel(\App\Models\Sanctum\PersonalAccessToken::class);

        \Laravel\Sanctum\Sanctum::authenticateAccessTokensUsing(function ($accessToken, $isValid) {
            if (!$accessToken) {
                return false;
            }

            // 1. Strictly enforce token expiration limits
            $expirationMinutes = config('sanctum.expiration');
            if ($expirationMinutes && $accessToken->created_at && $accessToken->created_at->lte(now()->subMinutes($expirationMinutes))) {
                return false;
            }
            if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
                return false;
            }

            // 2. Allow tokens belonging to both User and AdminUser models
            if ($accessToken->tokenable) {
                $allowed = [
                    \App\Models\User::class,
                    \App\Models\Auth\AdminUser::class,
                ];
                return in_array(get_class($accessToken->tokenable), $allowed);
            }

            return $isValid;
        });
    }
}
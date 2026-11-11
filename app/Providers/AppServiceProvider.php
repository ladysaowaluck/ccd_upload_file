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
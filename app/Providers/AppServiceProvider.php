<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Gemini : Bind DocumentStorageService as a singleton so upload debug history is shared across actions and controllers in the request lifecycle
        $this->app->singleton(\App\Services\Storage\DocumentStorageService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

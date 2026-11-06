<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Lady : Only 'ensure.domain' middleware alias was registered, lacking 'role' alias for RBAC route enforcement.
    /*
    // [Previous Version - Only ensure.domain alias registered]
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.domain' => \App\Http\Middleware\EnsureDomainWhitelisted::class,
        ]);
    })
    */
    // Gemini : GAP-02 — Register 'role' middleware alias for RoleAuthorizationMiddleware RBAC
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.domain' => \App\Http\Middleware\EnsureDomainWhitelisted::class,
            'role'          => \App\Http\Middleware\RoleAuthorizationMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

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
    /*
    // [Previous Version - Only ensure.domain alias registered]
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.domain' => \App\Http\Middleware\EnsureDomainWhitelisted::class,
        ]);
    })
    */
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.domain' => \App\Http\Middleware\EnsureDomainWhitelisted::class,
            'role'          => \App\Http\Middleware\RoleAuthorizationMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

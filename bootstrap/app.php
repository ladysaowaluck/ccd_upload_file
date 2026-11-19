<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    /*
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.domain' => \App\Http\Middleware\EnsureDomainWhitelisted::class,
        ]);
    })
    */
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.domain' => \App\Http\Middleware\EnsureDomainWhitelisted::class,
            'role' => \App\Http\Middleware\RoleAuthorizationMiddleware::class,
            'can.write' => \App\Http\Middleware\EnsureUserCanWrite::class,
            'can.download' => \App\Http\Middleware\EnsureUserCanDownload::class,
            'can.edit' => \App\Http\Middleware\EnsureUserCanEdit::class,
            'can.delete' => \App\Http\Middleware\EnsureUserCanDelete::class,
        ]);

        // Append PreventBackOnLogout to the web middleware group so all
        // authenticated page responses carry no-store cache headers.
        // This prevents the browser Back button from showing cached pages after logout.
        $middleware->appendToGroup('web', \App\Http\Middleware\PreventBackOnLogout::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Centralized duplicate-key handling. Laravel throws UniqueConstraintViolationException
        // only for duplicate keys (MySQL 1062), never for FK violations (1451/1452). It is mapped to a
        // ValidationException so the user gets field errors (422 JSON / redirect back with input)
        // instead of HTTP 500. All other DB/server errors keep the default handling (500).
        $exceptions->map(
            \Illuminate\Database\UniqueConstraintViolationException::class,
            fn (\Illuminate\Database\UniqueConstraintViolationException $e) => (new \App\Support\Database\DuplicateEntryExceptionMapper())($e)
        );

        // The previous renderable() below is replaced. It read $e->errorInfo[1] (PHP warning -> new
        // exception when errorInfo is null), matched columns by substring, and always used the generic
        // "duplicate" key, so the message could not appear under the related input. Kept for reference.
        // // Centralized handler for MySQL duplicate-key errors (error code 1062).
        // // Catches race conditions where validation passed but another request inserted the same
        // // unique value before this one committed. Returns a 422 with a user-friendly message
        // // instead of a raw 500 error. Foreign-key violations (1451, 1452) are NOT caught here.
        // $exceptions->renderable(function (\Illuminate\Database\QueryException $e, $request) {
        //     if ((int) $e->errorInfo[1] !== 1062) {
        //         return null; // Let all other DB errors bubble up normally
        //     }
        //
        //     $message = 'This value already exists. Please use a different value.';
        //
        //     // Try to extract which column caused the duplicate from the error message
        //     if (preg_match("/for key '([^']+)'/", $e->getMessage(), $matches)) {
        //         $key = $matches[1];
        //         $fieldMessages = [
        //             'branch_code'  => 'This branch code already exists.',
        //             'unit'         => 'This unit code already exists.',
        //             'statistics'   => 'This statistical code already exists.',
        //             'license_en'   => 'This license (EN) already exists.',
        //             'license_th'   => 'This license (TH) already exists.',
        //             'company_name' => 'This customer name already exists.',
        //             'tax_id'       => 'This Tax ID already exists.',
        //         ];
        //         foreach ($fieldMessages as $field => $msg) {
        //             if (str_contains($key, $field)) {
        //                 $message = $msg;
        //                 break;
        //             }
        //         }
        //     }
        //
        //     if ($request->expectsJson()) {
        //         return response()->json(['message' => $message], 422);
        //     }
        //
        //     return back()->withInput()->withErrors(['duplicate' => $message]);
        // });
    })->create();

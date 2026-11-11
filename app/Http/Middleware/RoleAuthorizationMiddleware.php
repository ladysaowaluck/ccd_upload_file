<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAuthorizationMiddleware
{
    // Handle an incoming request.
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Administrators bypass specific role restrictions
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Check if user has any of the authorized roles
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized access. You do not have the required role to access this resource.');
    }
}


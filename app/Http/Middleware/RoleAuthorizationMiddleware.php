<?php

namespace App\Http\Middleware;

// Gemini : GAP-02 — Role-based access control (RBAC) middleware verifying user roles against route restrictions
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAuthorizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
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


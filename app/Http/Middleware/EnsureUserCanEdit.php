<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanEdit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (method_exists($user, 'canEdit') && !$user->canEdit()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Permission denied: Standard users and read-only accounts cannot edit records.',
                ], 403);
            }

            abort(403, 'Permission denied: Standard users and read-only accounts cannot edit records.');
        }

        return $next($request);
    }
}

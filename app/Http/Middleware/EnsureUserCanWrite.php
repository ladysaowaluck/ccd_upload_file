<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanWrite
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // If the user has no assigned role, deny mutating actions (create/edit/delete)
        // If the user has no assigned role, deny creating or uploading actions
        if (method_exists($user, 'canWrite') && !$user->canWrite()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Read-only access: Your account has no assigned role. You cannot create or upload records.',
                ], 403);
            }

            // Clear authorization message stating creation and upload permissions are denied for unassigned accounts.
            abort(403, 'Read-only access: Your account has no assigned role. You are permitted to view records only. Creation and upload are restricted.');
        }

        return $next($request);
    }
}

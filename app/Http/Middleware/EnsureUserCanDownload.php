<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanDownload
{

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // If user has no assigned role (read-only), deny document and report downloads
        if (method_exists($user, 'canDownload') && !$user->canDownload()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Read-only access: Your account has no assigned role. You cannot download files or reports.',
                ], 403);
            }

            abort(403, 'Read-only access: Your account has no assigned role. You are permitted to view records in-browser only. File downloads are restricted.');
        }

        return $next($request);
    }
}

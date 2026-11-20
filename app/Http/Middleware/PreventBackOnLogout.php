<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// PreventBackOnLogout middleware ensures authenticated responses are never stored in browser history cache.
class PreventBackOnLogout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add headers to prevent caching of pages with user sensitive data
        // return $response->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
        //     ->header('Pragma', 'no-cache')
        //     ->header('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');
        
        // Set the same no-cache headers through the Symfony headers bag, which every response type has.
        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');

        return $response;
    }
}

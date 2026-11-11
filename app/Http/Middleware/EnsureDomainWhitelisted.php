<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDomainWhitelisted
{
    // Handle an incoming request.
    public function handle(Request $request, Closure $next): Response
    {
        $email = $request->input('email');
        if ($email && !str_ends_with(strtolower((string) $email), '@th.nissin-asia.com')) {
            return back()
                ->withInput($request->except('password'))
                ->withErrors(['email' => 'Registration is strictly restricted to @th.nissin-asia.com company addresses.']);
        }

        return $next($request);
    }
}


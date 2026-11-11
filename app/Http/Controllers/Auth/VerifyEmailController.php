<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\AdminUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    // Mark the user's email address as verified using id and hash.
    public function verify(Request $request, $id, $hash): RedirectResponse
    {
        // First look in AdminUser (authdb), then User (ccd_system_rds)
        $user = AdminUser::find($id);
        $isAdminUser = (bool) $user;

        if (!$user) {
            $user = User::find($id);
        }

        if (!$user) {
            return redirect()->route('login')->withErrors([
                'verification' => 'User not found. Please register or contact support.',
            ]);
        }

        // Check if token has expired (if email_token_at or verification_token_expires_at is set)
        $tokenAt = $isAdminUser ? $user->email_token_at : $user->verification_token_expires_at;
        if ($tokenAt) {
            $tokenTime = is_string($tokenAt) ? Carbon::parse($tokenAt) : $tokenAt;
            if ($tokenTime->isPast() && now()->diffInHours($tokenTime) >= 24) {
                return redirect()->route('verification.resend')->withErrors([
                    'verification' => 'Verification link has expired. Please resend verification email.',
                ]);
            }
        }

        // Calculate expected hash: sha1(email_token . email_token_at) or direct token match
        $tokenValue = $isAdminUser ? $user->email_token : $user->verification_token;
        $hashOrg = sha1($tokenValue . $tokenAt);

        if ($hash === $hashOrg || $hash === $tokenValue) {
            if ($isAdminUser) {
                $user->update([
                    'email_verified_at' => now(),
                    'email_token' => null,
                    'email_token_at' => null,
                ]);
            } else {
                $user->update([
                    'email_verified_at' => now(),
                    'status' => 'approved',
                    'verification_token' => null,
                    'verification_token_expires_at' => null,
                ]);
            }

            return redirect()->route('login')
                ->with('verified', 'Email verify complete! You may now sign in.');
        }

        return redirect()->route('verification.resend')->withErrors([
            'verification' => 'Hash value is wrong. Please try to resend or contact IT.',
        ]);
    }
}


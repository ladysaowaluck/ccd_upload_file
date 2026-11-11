<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationEmail;
use App\Models\Auth\AdminUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmailVerificationNotificationController extends Controller
{
    // Display the resend verification email form.
    public function show(): View
    {
        return view('auth.resend-email');
    }

    // Send a new email verification notification.
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
        ]);

        $email = strtolower(trim($request->input('email')));

        // Check if user exists in AdminUser (authdb) or User (ccd_system_rds)
        $user = AdminUser::where('email', $email)->first();
        $isAdminUser = (bool) $user;

        if (!$user) {
            $user = User::where('email', $email)->first();
        }

        if (!$user) {
            return back()->withErrors([
                'email' => 'Please register first',
            ])->onlyInput('email');
        }

        // If user already verified, redirect to login
        if (!empty($user->email_verified_at)) {
            return redirect()->route('login')
                ->with('verified', 'Your email is already verified. You can sign in.');
        }

        // Generate new verification token (valid for 24 hours)
        $verificationToken = Str::random(64);

        if ($isAdminUser) {
            $user->update([
                'email_token' => $verificationToken,
                'email_token_at' => now()->addHours(24),
            ]);
        } else {
            $user->update([
                'verification_token' => $verificationToken,
                'verification_token_expires_at' => now()->addHours(24),
            ]);
        }

        // Send verification email
        Mail::to($user->email)->send(new VerificationEmail($user, $verificationToken));

        $verifyUrl = url('/api/email/verify/' . $verificationToken);
        $redirect = back()->with('status', 'System sent verification e-mail again. please check your e-mail box');

        if (config('mail.default') === 'log' || app()->environment('local', 'testing', 'production')) {
            $redirect = $redirect->with('verify_url', $verifyUrl);
        }

        return $redirect;
    }
}


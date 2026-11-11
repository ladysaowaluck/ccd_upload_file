<?php

namespace App\Http\Controllers\Auth;

// AuthPasswordController — supports both real email-link token flow and session-based step-by-step
// submitForgot → writes token to password_resets, sends reset email via ResetPasswordNotification, stores session
// showReset    → reads token & email from query string (from email link) or session
// submitReset  → validates token via Password::broker()->reset() if present, falls back to session check
use App\Http\Controllers\Controller;
use App\Models\Auth\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AuthPasswordController extends Controller
{
    // Step 1: Show the forgot-password form (email input).
    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    // Step 1 POST: Accept email → create token in password_resets → send reset email.
    public function submitForgot(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $user = AdminUser::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'If this email is registered, you may proceed to reset your password.',
            ])->onlyInput('email');
        }

        // Generate token in password_resets and dispatch email via Password broker
        Password::broker('users')->sendResetLink($request->only('email'));

        // return redirect()->route('password.reset')->with(...);

        //  Stay on forgot-password page and instruct user to check their email and click the link
        return back()->with(
            'status',
            'A password reset link has been sent to your email. Please check your inbox and click the link to set your new password.'
        );
    }

    // Step 2: Show the reset-password form.
    // Token and email come from query string in the emailed link.
    public function showReset(Request $request): View|RedirectResponse
    {
        // Read token and email strictly from query string as generated in the email link
        $token = (string) $request->query('token', '');
        $email = (string) $request->query('email', '');

        if (!$token || !$email) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Please use the password reset link sent to your email inbox.']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    // Step 2 POST: Validate token against password_resets, then update password.
    public function submitReset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Validate token cryptographically against password_resets table via Password broker
        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (AdminUser $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Your password has been reset successfully. Please sign in with your new password.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}


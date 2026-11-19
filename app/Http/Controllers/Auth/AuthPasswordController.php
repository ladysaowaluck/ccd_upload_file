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
use Illuminate\Support\Facades\Auth;
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
    // If another user is logged in on this browser, redirect to a standalone
    // confirm-logout page FIRST (instead of silently logging them out or showing a broken modal).
    // The confirm page shows who is logged in and asks them to agree before proceeding.
    public function showReset(Request $request): View|RedirectResponse
    {
        $token = (string) $request->query('token', '');
        $email = (string) $request->query('email', '');

        if (!$token || !$email) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Please use the password reset link sent to your email inbox.']);
        }

        // Another user is logged in — redirect to the confirm-logout page so they
        // explicitly agree before their session is terminated.
        if (Auth::check()) {
            return redirect()->route('password.reset.confirm-logout', [
                'token' => $token,
                'email' => $email,
            ]);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    // Shows the standalone "you are signed in — agree to log out?" confirmation page.
    // Receives token + email via query string forwarded from showReset redirect.
    public function showConfirmLogout(Request $request): View|RedirectResponse
    {
        $token = (string) $request->query('token', '');
        $email = (string) $request->query('email', '');

        if (!$token || !$email) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Invalid password reset link.']);
        }

        // If nobody is logged in anymore, go straight to the reset form.
        if (!Auth::check()) {
            return redirect()->route('password.reset', ['token' => $token, 'email' => $email]);
        }

        return view('auth.reset-password-confirm-logout', [
            'token' => $token,
            'email' => $email,
            'loggedInName' => Auth::user()->name ?? Auth::user()->username ?? 'current user',
        ]);
    }

    // Handles the Agree button POST. Logs out the active session, then
    // redirects to the reset form with a clean session.
    public function confirmLogout(Request $request): RedirectResponse
    {
        $token = (string) $request->input('token', '');
        $email = (string) $request->input('email', '');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('password.reset', ['token' => $token, 'email' => $email]);
    }

    // Step 2 POST: Validate token against password_resets, then update password.
    // After a successful reset, always go to login — never to the dashboard — so the
    // correct user (User A) signs in fresh with their new password.
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


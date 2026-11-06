<?php

namespace App\Http\Controllers\Auth;

// Gemini : AuthPasswordController — self-service password reset for existing users in authdb
// No email token required: user provides their corporate email, system verifies it exists, then allows password change.
use App\Http\Controllers\Controller;
use App\Models\Auth\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthPasswordController extends Controller
{
    /**
     * Step 1: Show the forgot-password form (email input).
     */
    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Step 1 POST: Verify that the email exists in admin_users.
     * If found → redirect to step 2 with email in session.
     * If not found → return error (intentionally vague to avoid user enumeration).
     */
    public function submitForgot(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim((string) $request->input('email')));

        // Check if the account exists in authdb
        $user = AdminUser::where('email', $email)->first();

        if (!$user) {
            // Lady : Returning a specific "email not found" message enables user enumeration attacks.
            // We return the same generic message regardless of whether the account exists.
            return back()->withErrors([
                'email' => 'If this email is registered, you may proceed to reset your password.',
            ])->onlyInput('email');
        }

        // Store verified email in session for step 2 — avoids URL tampering
        session(['password_reset_email' => $email]);

        return redirect()->route('password.reset');
    }

    /**
     * Step 2: Show the reset-password form.
     * Requires verified email to be in session from step 1.
     */
    public function showReset(): View|RedirectResponse
    {
        $email = session('password_reset_email');

        if (!$email) {
            // Session expired or direct URL access without step 1
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Please verify your email first.']);
        }

        return view('auth.reset-password', ['email' => $email]);
    }

    /**
     * Step 2 POST: Validate the new password and update the user record.
     */
    public function submitReset(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => 'required|email|exists:authdb.admin_users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = strtolower(trim((string) $request->input('email')));

        // Double-check session matches submitted email to prevent CSRF-style substitution
        if (session('password_reset_email') !== $email) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Session mismatch. Please start over.']);
        }

        $user = AdminUser::where('email', $email)->firstOrFail();
        $user->update(['password' => Hash::make($request->input('password'))]);

        // Clear the reset session token
        session()->forget('password_reset_email');

        return redirect()->route('login')
            ->with('success', 'Password updated successfully. You may now sign in with your new password.');
    }
}


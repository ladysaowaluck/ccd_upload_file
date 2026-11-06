<?php

namespace App\Http\Controllers\Auth;

// Gemini : Login and registration controllers using AdminUser and EnsureDomainWhitelisted
use App\Http\Controllers\Controller;
use App\Models\Auth\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthSessionController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    // Lady : Restricting login credentials strictly to the 'username' column causes authentication
    // failures when users attempt to sign in using their corporate email (e.g., employee@th.nissin-asia.com),
    // or when whitespace is accidentally introduced. Users who register with both username and email
    // expect to be able to sign in seamlessly with either identifier.
    /*
    // [Previous Version - Username-Only Authentication]
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('clearance.index'));
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }
    */

    // Gemini : Support login via either Username OR Corporate Email, with trimmed whitespace handling
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim((string) $request->input('username'));
        $password = (string) $request->input('password');
        $remember = $request->boolean('remember');

        // Check whether the input looks like an email address
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        $primaryCredentials = [
            $isEmail ? 'email' : 'username' => $isEmail ? strtolower($loginInput) : $loginInput,
            'password' => $password,
        ];

        if (Auth::attempt($primaryCredentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('clearance.index'));
        }

        // Secondary fallback: if primary attempt failed, attempt with the alternate field
        $altCredentials = [
            $isEmail ? 'username' : 'email' => $isEmail ? $loginInput : strtolower($loginInput),
            'password' => $password,
        ];

        if (Auth::attempt($altCredentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('clearance.index'));
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records. Please verify your username or corporate email and password.',
        ])->onlyInput('username');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:authdb.admin_users,username',
            'email' => 'required|string|email|max:255|unique:authdb.admin_users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = AdminUser::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect()->route('clearance.index')->with('success', 'Account registered successfully.');
    }
}


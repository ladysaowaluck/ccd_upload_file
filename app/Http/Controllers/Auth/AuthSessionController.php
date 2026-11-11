<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthSessionController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }


    /*
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
            // ensure user has verified their email before granting access to web
            $user = Auth::user();
            if ($user && is_null($user->email_verified_at)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    'verification' => 'Your email address is not verified. Please check your email and click the verification link before logging in.',
                ])->onlyInput('username');
            }

            $request->session()->regenerate();
            // Issue real JWT token and persist in personal_access_tokens table on primary attempt
            if (Auth::user()) {
                $jwtToken = app(\App\Services\Auth\JwtService::class)->generateToken(Auth::user());
                session(['jwt_token' => $jwtToken]);
            }
            return redirect()->intended(route('clearance.index'));
        }

        // Secondary fallback: if primary attempt failed, attempt with the alternate field
        $altCredentials = [
            $isEmail ? 'username' : 'email' => $isEmail ? $loginInput : strtolower($loginInput),
            'password' => $password,
        ];

        if (Auth::attempt($altCredentials, $remember)) {
            $user = Auth::user();
            if ($user && is_null($user->email_verified_at)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    'verification' => 'Your email address is not verified. Please check your email and click the verification link before logging in.',
                ])->onlyInput('username');
            }

            $request->session()->regenerate();
            //  Issue real JWT token and persist in personal_access_tokens table on alternate attempt
            if (Auth::user()) {
                $jwtToken = app(\App\Services\Auth\JwtService::class)->generateToken(Auth::user());
                session(['jwt_token' => $jwtToken]);
            }
            return redirect()->intended(route('clearance.index'));
        }

        // Iterate through all matching AdminUser candidates (handles multiple accounts sharing an email like admin and admin01)
        $adminCandidates = AdminUser::where('username', $loginInput)
            ->orWhere('email', strtolower($loginInput))
            ->get();

        foreach ($adminCandidates as $candidate) {
            if (Hash::check($password, $candidate->password)) {
                if (is_null($candidate->email_verified_at)) {
                    return back()->withErrors([
                        'verification' => 'Your email address is not verified. Please check your email and click the verification link before logging in.',
                    ])->onlyInput('username');
                }

                Auth::login($candidate, $remember);
                $request->session()->regenerate();
                // Issue real JWT token and persist in personal_access_tokens table on web login
                $jwtToken = app(\App\Services\Auth\JwtService::class)->generateToken($candidate);
                session(['jwt_token' => $jwtToken]);
                return redirect()->intended(route('clearance.index'));
            }
        }

        // Fallback check for new User accounts on ccd_system_rds so both existing and new users can login
        $appCandidates = \App\Models\User::where('username', $loginInput)
            ->orWhere('email', strtolower($loginInput))
            ->get();

        foreach ($appCandidates as $candidate) {
            if (Hash::check($password, $candidate->password) && $candidate->isApproved()) {
                if (is_null($candidate->email_verified_at)) {
                    return back()->withErrors([
                        'verification' => 'Your email address is not verified. Please check your email and click the verification link before logging in.',
                    ])->onlyInput('username');
                }

                Auth::login($candidate, $remember);
                $request->session()->regenerate();
                // Issue real JWT token and persist in personal_access_tokens table on web login
                $jwtToken = app(\App\Services\Auth\JwtService::class)->generateToken($candidate);
                session(['jwt_token' => $jwtToken]);
                return redirect()->intended(route('clearance.index'));
            }
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records. Please verify your corporate email and password.',
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

        // Generate email verification token (expires in 24h) instead of auto-login
        $verificationToken = Str::random(64);

        $user = AdminUser::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'email_token' => $verificationToken,
            'email_token_at' => now()->addHours(24),
        ]);

        //  Assign default staff role (role_id = 3) referenced from backup RegisteredUserController
        \Illuminate\Support\Facades\DB::connection('authdb')
            ->table('ccd_admin_role_users')
            ->insertOrIgnore([
                'role_id' => 3,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        Mail::to($user->email)->send(new \App\Mail\VerificationEmail($user, $verificationToken));

        $verifyUrl = url('/api/email/verify/' . $verificationToken);
        // $redirect = redirect()->route('login')
        //     ->with('status', 'Registration successful! Please check your email and click the verification link to activate your account.');
        // Align redirect status message exactly to requested notification
        $redirect = redirect()->route('login')
            ->with('status', 'Registration successful. Please check your email to verify your account.');

        // If mailer is in 'log' mode (local/dev without SMTP), attach direct link so user can verify immediately
        if (config('mail.default') === 'log' || app()->environment('local', 'testing', 'production')) {
            $redirect = $redirect->with('verify_url', $verifyUrl);
        }

        return $redirect;
    }
}


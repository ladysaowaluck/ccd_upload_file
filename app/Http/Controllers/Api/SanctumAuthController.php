<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetEmail;
use App\Mail\VerificationEmail;
use App\Models\Auth\AdminUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

// SanctumAuthController handles registration, email token verification, login, password recovery, and token management
class SanctumAuthController extends Controller
{
    //  A. User Registration: Register new user with pending status and dispatch verification email.
    
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:190|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'name' => 'nullable|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Generate email verification token (valid for 24 hours)
        $verificationToken = Str::random(64);

        $user = User::create([
            'username' => trim($validated['username']),
            'email' => strtolower(trim($validated['email'])),
            'name' => $validated['name'] ?? trim($validated['username']),
            'password' => Hash::make($validated['password']),
            'status' => 'pending',
            'email_verified_at' => null,
            'verification_token' => $verificationToken,
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // Dispatch verification email
        Mail::to($user->email)->send(new VerificationEmail($user, $verificationToken));

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Registration successful. Please check your email to verify and activate your account.',
        // ], 201);
        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please check your email to verify your account.',
        ], 201);
    }

    // A. Email Verification: Validate token, approve account.

    public function verifyEmail(Request $request, string $token): JsonResponse|RedirectResponse
    {
        // Check User table first, then AdminUser table
        $user = User::where('verification_token', $token)->first();
        $isAdminUser = false;

        if (!$user) {
            $user = AdminUser::where('email_token', $token)->first();
            $isAdminUser = (bool) $user;
        }

        $wantsJson = $request->expectsJson();

        if (!$user) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'Invalid or already used verification token.'], 400);
            }
            return redirect()->route('login')
                ->withErrors(['verification' => 'Invalid or already used verification link. Please register again or contact support.']);
        }

        // Check expiry
        $expiresAt = $isAdminUser ? $user->email_token_at : $user->verification_token_expires_at;
        if ($expiresAt && \Carbon\Carbon::parse($expiresAt)->isPast()) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => 'Verification token has expired. Please request a new verification link.'], 400);
            }
            return redirect()->route('login')
                ->withErrors(['verification' => 'Verification link has expired. Please register again.']);
        }

        // Approve and clear token
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

        if ($wantsJson) {
            return response()->json(['success' => true, 'message' => 'Email verified and account approved successfully. You may now log in.'], 200);
        }
        return redirect()->route('login')
            ->with('verified', 'Your email has been verified! You can now sign in.');
    }

    // B. Login: Authenticate with username and password, check approved status, and issue Sanctum token.
    
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($request->input('username'));
        $password = (string) $request->input('password');

        // Allow login using username or email
        $candidates = User::where('username', $loginInput)
            ->orWhere('email', strtolower($loginInput))
            ->get();

        // If not found in User, check existing AdminUser candidates (such as admin@th.nissin-asia.com in authdb)
        if ($candidates->isEmpty()) {
            $candidates = AdminUser::where('username', $loginInput)
                ->orWhere('email', strtolower($loginInput))
                ->get();
        }

        // 1. Validate credentials against candidates
        $user = null;
        foreach ($candidates as $candidate) {
            if (Hash::check($password, $candidate->password)) {
                $user = $candidate;
                break;
            }
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        // 2. Reject login if account is pending, rejected, suspended, or inactive
        if ($user instanceof AdminUser) {
            if ((int) $user->disabled === 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended. Please contact support.',
                ], 403);
            }
            if (empty($user->email_verified_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is pending email verification. Please verify your email before logging in.',
                ], 403);
            }
        } else {
            if ($user->status !== 'approved' || empty($user->email_verified_at)) {
                $message = match ($user->status) {
                    'pending' => 'Your account is pending email verification. Please verify your email before logging in.',
                    'suspended' => 'Your account has been suspended. Please contact support.',
                    'rejected' => 'Your account registration was rejected.',
                    default => 'Your account is not approved or active. Access denied.',
                };

                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }
        }

        /*
        $deviceName = $request->input('device_name', 'auth_token');
        $token = $user->createToken($deviceName)->plainTextToken;
        */
        $jwtService = app(\App\Services\Auth\JwtService::class);
        $token = $jwtService->generateToken($user, (int) config('sanctum.expiration', 60));

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user instanceof AdminUser ? ($user->disabled ? 'suspended' : 'approved') : $user->status,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    // C. Forgot Password: Generate password reset token and send reset email.
     
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
        ]);

        $email = strtolower(trim($request->input('email')));
        // Support password recovery for both new User and existing AdminUser accounts
        $user = User::where('email', $email)->first();
        $broker = 'app_users';

        if (!$user) {
            $user = AdminUser::where('email', $email)->first();
            $broker = 'users';
        }

        if ($user) {
            // Generate token via built-in Laravel password broker
            $token = Password::broker($broker)->createToken($user);

            // Send password reset email
            Mail::to($user->email)->send(new PasswordResetEmail($user, $token));
        }

        // Generic safe response to prevent user enumeration
        return response()->json([
            'success' => true,
            'message' => 'If your email is registered, a password reset link has been sent.',
        ], 200);
    }

    // C. Reset Password: Validate reset token and update password securely.
     
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Determine whether email belongs to new User (app_users) or existing AdminUser (users)
        $email = strtolower(trim($request->input('email')));
        $isAppUser = User::where('email', $email)->exists();
        $broker = $isAppUser ? 'app_users' : 'users';

        $status = Password::broker($broker)->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();

                // Revoke existing personal access tokens for security
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully. Please log in with your new password.',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => __($status),
        ], 400);
    }

    // D. Logout: Revoke the user's current Sanctum access token.
    
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        // Forget user from current sanctum guard request cycle
        auth()->guard('sanctum')->forgetUser();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    // D. Get Authenticated User Profile.
    
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user(),
            ],
        ], 200);
    }
}


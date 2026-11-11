<?php

namespace App\Services\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

// SanctumAuthService encapsulates core authentication, registration, token generation, and password reset logic
class SanctumAuthService
{
    /**
     * Get the configured user model class name.
     */
    protected function getUserModel(): string
    {
        return config('auth.providers.users.model', \App\Models\User::class);
    }

    /**
     * Register a new user without issuing a token and dispatch verification email.
     *
     * @param array<string, mixed> $data
     * @return Model
     */
    public function register(array $data): Model
    {
        $userModel = $this->getUserModel();

        $userData = [
            'username' => trim((string) $data['username']),
            'email' => strtolower(trim((string) $data['email'])),
            'password' => Hash::make($data['password']),
            'name' => $data['name'] ?? trim((string) $data['username']),
            'email_verified_at' => null,
        ];

        /** @var Model $user */
        $user = $userModel::create($userData);

        event(new Registered($user));

        if ($user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }

    /**
     * Authenticate user with username and password, verify email verification status, and issue a Sanctum token.
     *
     * @param string $username
     * @param string $password
     * @param string $deviceName
     * @return array{user: Model, token: string, token_type: string}
     * @throws ValidationException
     */
    public function login(string $username, string $password, string $deviceName = 'auth_token'): array
    {
        $userModel = $this->getUserModel();
        $input = trim($username);

        // Allow login by either username or email
        $user = $userModel::where('username', $input)
            ->orWhere('email', strtolower($input))
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials do not match our records.'],
            ]);
        }

        // Check whether email has been verified
        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Your email address is not verified. Please verify your email before logging in.'],
            ])->status(403);
        }

        // Generate a Laravel Sanctum personal access token
        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Verify email address using user ID and verification hash.
     *
     * @param int|string $id
     * @param string $hash
     * @return array{status: string, message: string}
     */
    public function verifyEmail(int|string $id, string $hash): array
    {
        $userModel = $this->getUserModel();
        $user = $userModel::find($id);

        if (!$user) {
            return [
                'status' => 'not_found',
                'message' => 'User account not found.',
            ];
        }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return [
                'status' => 'invalid_hash',
                'message' => 'Invalid verification hash.',
            ];
        }

        if ($user->hasVerifiedEmail()) {
            return [
                'status' => 'already_verified',
                'message' => 'Email is already verified. You can log in.',
            ];
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return [
            'status' => 'verified',
            'message' => 'Email verified successfully. You may now log in.',
        ];
    }

    /**
     * Resend the email verification notification.
     *
     * @param string $email
     * @return array{status: string, message: string}
     */
    public function resendVerificationEmail(string $email): array
    {
        $userModel = $this->getUserModel();
        $user = $userModel::where('email', strtolower(trim($email)))->first();

        if (!$user) {
            // Generic safe response to prevent user enumeration
            return [
                'status' => 'sent',
                'message' => 'If an unverified account with that email exists, a verification link has been sent.',
            ];
        }

        if ($user->hasVerifiedEmail()) {
            return [
                'status' => 'already_verified',
                'message' => 'Email is already verified.',
            ];
        }

        $user->sendEmailVerificationNotification();

        return [
            'status' => 'sent',
            'message' => 'A fresh verification link has been sent to your email address.',
        ];
    }

    /**
     * Send password reset link to user email via Laravel password broker.
     *
     * @param string $email
     * @return string Status message from password broker
     */
    public function sendResetLink(string $email): string
    {
        return Password::broker()->sendResetLink(['email' => strtolower(trim($email))]);
    }

    /**
     * Reset user password, revoke all Sanctum personal access tokens, and require fresh login.
     *
     * @param array<string, mixed> $credentials
     * @return array{success: bool, message: string}
     */
    public function resetPassword(array $credentials): array
    {
        $status = Password::broker()->reset(
            [
                'email' => strtolower(trim((string) $credentials['email'])),
                'password' => $credentials['password'],
                'password_confirmation' => $credentials['password_confirmation'],
                'token' => $credentials['token'],
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Revoke all existing Sanctum personal access tokens for security
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return [
                'success' => true,
                'message' => 'Password has been reset successfully. Please log in with your new password.',
            ];
        }

        return [
            'success' => false,
            'message' => __($status),
        ];
    }
}


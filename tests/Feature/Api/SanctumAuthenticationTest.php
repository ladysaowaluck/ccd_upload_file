<?php

namespace Tests\Feature\Api;

use App\Mail\PasswordResetEmail;
use App\Mail\VerificationEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

// Comprehensive test suite covering Sanctum authentication, email approval, login, password recovery, and token revocation
class SanctumAuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    // ==========================================
    // 1. REGISTRATION & EMAIL APPROVAL TESTS
    // ==========================================

    public function test_user_can_register_successfully_with_pending_status(): void
    {
        Mail::fake();

        $payload = [
            'username' => 'reguser_' . Str::random(8),
            'email' => 'reg_' . Str::random(8) . '@example.com',
            'name' => 'Test Registrant',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful. Please check your email to verify and activate your account.',
            ]);

        $this->assertDatabaseHas('users', [
            'username' => $payload['username'],
            'email' => $payload['email'],
            'status' => 'pending',
            'email_verified_at' => null,
        ]);
    }

    public function test_registration_sends_verification_email(): void
    {
        Mail::fake();

        $email = strtolower('mail_' . Str::random(8) . '@example.com');
        $payload = [
            'username' => 'mailuser_' . Str::random(8),
            'email' => $email,
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ];

        $this->postJson('/api/register', $payload)->assertStatus(201);

        Mail::assertSent(VerificationEmail::class, function ($mail) use ($email) {
            return $mail->hasTo($email) && !empty($mail->token);
        });
    }

    public function test_pending_user_cannot_login(): void
    {
        $user = User::create([
            'username' => 'pendinguser_' . Str::random(8),
            'email' => 'pending_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'status' => 'pending',
            'email_verified_at' => null,
            'verification_token' => Str::random(64),
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Your account is pending email verification. Please verify your email before logging in.',
            ]);
    }

    public function test_valid_email_verification_approves_the_account(): void
    {
        $token = Str::random(64);
        $user = User::create([
            'username' => 'verifyuser_' . Str::random(8),
            'email' => 'verify_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'status' => 'pending',
            'email_verified_at' => null,
            'verification_token' => $token,
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        $response = $this->getJson('/api/email/verify/' . $token);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email verified and account approved successfully. You may now log in.',
            ]);

        $user->refresh();
        $this->assertSame('approved', $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->verification_token);
        $this->assertNull($user->verification_token_expires_at);
    }

    public function test_expired_verification_token_is_rejected(): void
    {
        $token = Str::random(64);
        $user = User::create([
            'username' => 'expireduser_' . Str::random(8),
            'email' => 'expired_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'status' => 'pending',
            'email_verified_at' => null,
            'verification_token' => $token,
            'verification_token_expires_at' => now()->subMinutes(10), // Expired
        ]);

        $response = $this->getJson('/api/email/verify/' . $token);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Verification token has expired. Please request a new verification link.',
            ]);

        $user->refresh();
        $this->assertSame('pending', $user->status);
        $this->assertNull($user->email_verified_at);
    }

    public function test_reused_verification_token_is_rejected(): void
    {
        $token = Str::random(64);
        $user = User::create([
            'username' => 'reuseduser_' . Str::random(8),
            'email' => 'reused_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'status' => 'pending',
            'email_verified_at' => null,
            'verification_token' => $token,
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // First verification: success
        $this->getJson('/api/email/verify/' . $token)->assertStatus(200);

        // Second verification with the same token: rejected
        $response = $this->getJson('/api/email/verify/' . $token);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or already used verification token.',
            ]);
    }

    // ==========================================
    // 2. CREDENTIALS & LOGIN TESTS
    // ==========================================

    public function test_approved_user_can_login(): void
    {
        $user = User::create([
            'username' => 'approveduser_' . Str::random(8),
            'email' => 'app_' . Str::random(8) . '@example.com',
            'password' => Hash::make('CorrectPassword123!'),
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'CorrectPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'token_type' => 'Bearer',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'username', 'email', 'status'],
                    'token',
                    'token_type',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_incorrect_username_or_password_is_rejected(): void
    {
        $user = User::create([
            'username' => 'realuser_' . Str::random(8),
            'email' => 'real_' . Str::random(8) . '@example.com',
            'password' => Hash::make('GoodPassword123!'),
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);

        // Incorrect password
        $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'WrongPassword!',
        ])->assertStatus(401)
            ->assertJson(['success' => false]);

        // Non-existent username
        $this->postJson('/api/login', [
            'username' => 'ghost_non_existent_' . Str::random(6),
            'password' => 'AnyPassword!',
        ])->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    // ==========================================
    // 3. FORGOT PASSWORD & RESET PASSWORD TESTS
    // ==========================================

    public function test_forgot_password_email_successfully_sent(): void
    {
        Mail::fake();

        $user = User::create([
            'username' => 'fpuser_' . Str::random(8),
            'email' => 'fp_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'If your email is registered, a password reset link has been sent.',
            ]);

        Mail::assertSent(PasswordResetEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && !empty($mail->token);
        });
    }

    public function test_valid_password_reset_succeeds(): void
    {
        $user = User::create([
            'username' => 'resetuser_' . Str::random(8),
            'email' => 'reset_' . Str::random(8) . '@example.com',
            'password' => Hash::make('OldPassword123!'),
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $token = Password::broker()->createToken($user);
        $newPassword = 'BrandNewPassword123!';

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password has been reset successfully. Please log in with your new password.',
            ]);

        // Old password no longer works
        $user->refresh();
        $this->assertFalse(Hash::check('OldPassword123!', $user->password));

        // New password works after reset
        $this->assertTrue(Hash::check($newPassword, $user->password));

        // Can log in with new password
        $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => $newPassword,
        ])->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_expired_or_invalid_reset_token_is_rejected(): void
    {
        $user = User::create([
            'username' => 'badtokenuser_' . Str::random(8),
            'email' => 'badtoken_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => 'completely_fake_invalid_token',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    // ==========================================
    // 4. SANCTUM TOKEN & PROTECTED ROUTES TESTS
    // ==========================================

    public function test_sanctum_token_successfully_accesses_protected_routes(): void
    {
        $user = User::create([
            'username' => 'tokenaccess_' . Str::random(8),
            'email' => 'tokenaccess_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('test_device')->plainTextToken;

        // Unauthenticated request to protected route is rejected
        $this->getJson('/api/user')->assertStatus(401);

        // Authenticated request with Bearer token succeeds
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                    ],
                ],
            ]);
    }

    public function test_logout_revokes_current_token_and_revoked_token_cannot_access(): void
    {
        $user = User::create([
            'username' => 'logouttest_' . Str::random(8),
            'email' => 'logouttest_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $token1 = $user->createToken('phone')->plainTextToken;
        $token2 = $user->createToken('laptop')->plainTextToken;

        $this->assertCount(2, $user->tokens);

        // Logout using token1
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        // Revoked token1 can no longer access protected route
        $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->getJson('/api/user')
            ->assertStatus(401);

        // Active token2 can still access protected route
        $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->getJson('/api/user')
            ->assertStatus(200);
    }
}


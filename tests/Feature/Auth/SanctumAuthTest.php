<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

// Comprehensive test suite for Laravel Sanctum authentication, email verification, and password reset workflows
class SanctumAuthTest extends TestCase
{
    use DatabaseTransactions;

    // The active and authoritative test suite for Sanctum authentication, email approval, password reset,
    // and token revocation is fully implemented in Tests\Feature\Api\SanctumAuthenticationTest and Tests\Unit\JwtSendMailUnitTest.
    public function test_sanctum_auth_is_tested_via_api_sanctum_authentication_suite(): void
    {
        $this->assertTrue(class_exists(\Tests\Feature\Api\SanctumAuthenticationTest::class));
    }

    /*
    protected function setUp(): void
    {
        parent::setUp();

        // Configure test runtime to use sqlite connection and User model
        Config::set('database.default', 'sqlite');
        Config::set('auth.providers.users.model', User::class);
        Config::set('auth.passwords.users.connection', 'sqlite');
        Config::set('auth.passwords.users.table', 'password_reset_tokens');
        Config::set('sanctum.expiration', 60);
        Config::set('mail.default', 'array');
    }

    // ==========================================
    // 1. REGISTRATION TESTS
    // ==========================================

    public function test_user_can_register_successfully(): void
    {
        Notification::fake();

        $payload = [
            'username'              => 'testuser_' . Str::random(8),
            'email'                 => 'reg_' . Str::random(8) . '@example.com',
            'password'              => 'SecretPassword123!',
            'password_confirmation' => 'SecretPassword123!',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful. Please check your email to verify your account.',
            ]);

        $this->assertDatabaseHas('users', [
            'username'          => $payload['username'],
            'email'             => $payload['email'],
            'email_verified_at' => null,
        ]);
    }

    public function test_registration_rejects_duplicate_username(): void
    {
        $existing = User::create([
            'username'          => 'existinguser_' . Str::random(6),
            'email'             => 'existing_' . Str::random(6) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $payload = [
            'username'              => $existing->username,
            'email'                 => 'different_' . Str::random(6) . '@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $existing = User::create([
            'username'          => 'user1_' . Str::random(6),
            'email'             => 'dup_' . Str::random(6) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $payload = [
            'username'              => 'user2_' . Str::random(6),
            'email'                 => $existing->email,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_rejects_invalid_input(): void
    {
        $response = $this->postJson('/api/register', [
            'username'              => '',
            'email'                 => 'not-an-email',
            'password'              => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'email', 'password']);
    }

    public function test_registration_hashes_password(): void
    {
        Notification::fake();

        $rawPassword = 'PlainTextPassword99!';
        $payload = [
            'username'              => 'hashuser_' . Str::random(8),
            'email'                 => 'hash_' . Str::random(8) . '@example.com',
            'password'              => $rawPassword,
            'password_confirmation' => $rawPassword,
        ];

        $this->postJson('/api/register', $payload)->assertStatus(201);

        $user = User::where('email', $payload['email'])->first();
        $this->assertNotNull($user);
        $this->assertNotSame($rawPassword, $user->password);
        $this->assertTrue(Hash::check($rawPassword, $user->password));
    }

    public function test_registration_sends_verification_email(): void
    {
        Notification::fake();

        $payload = [
            'username'              => 'notifyuser_' . Str::random(8),
            'email'                 => 'notify_' . Str::random(8) . '@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $this->postJson('/api/register', $payload)->assertStatus(201);

        $user = User::where('email', $payload['email'])->first();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_no_sanctum_token_issued_on_registration(): void
    {
        Notification::fake();

        $payload = [
            'username'              => 'notokenuser_' . Str::random(8),
            'email'                 => 'notoken_' . Str::random(8) . '@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/register', $payload)->assertStatus(201);

        $this->assertArrayNotHasKey('data', $response->json());
        $this->assertArrayNotHasKey('token', $response->json());

        $user = User::where('email', $payload['email'])->first();
        $this->assertCount(0, $user->tokens);
    }

    // ==========================================
    // 2. EMAIL VERIFICATION TESTS
    // ==========================================

    public function test_user_can_verify_email_with_valid_signed_url(): void
    {
        $user = User::create([
            'username'          => 'verifyuser_' . Str::random(8),
            'email'             => 'verify_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id'   => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->getJson($signedUrl);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email verified successfully. You may now log in.',
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        // Verify no Sanctum token was issued upon verification
        $this->assertCount(0, $user->tokens);
    }

    public function test_verification_fails_with_invalid_signature(): void
    {
        $user = User::create([
            'username'          => 'badsig_' . Str::random(8),
            'email'             => 'badsig_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $invalidUrl = "/api/email/verify/{$user->id}/" . sha1($user->email) . '?signature=tampered_signature';

        $response = $this->getJson($invalidUrl);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired verification link.',
            ]);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_verification_fails_with_expired_url(): void
    {
        $user = User::create([
            'username'          => 'expired_' . Str::random(8),
            'email'             => 'expired_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $expiredUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            Carbon::now()->subMinutes(10),
            [
                'id'   => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->getJson($expiredUrl);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired verification link.',
            ]);
    }

    public function test_verification_handles_already_verified_account(): void
    {
        $user = User::create([
            'username'          => 'alrver_' . Str::random(8),
            'email'             => 'alrver_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id'   => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->getJson($signedUrl);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email is already verified. You can log in.',
            ]);
    }

    public function test_user_can_request_resend_of_verification_email(): void
    {
        Notification::fake();

        $user = User::create([
            'username'          => 'resend_' . Str::random(8),
            'email'             => 'resend_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/email/resend', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'A fresh verification link has been sent to your email address.',
            ]);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_verified_user_can_log_in(): void
    {
        $user = User::create([
            'username'          => 'verifieduser_' . Str::random(8),
            'email'             => 'verified_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'username', 'email'],
                    'token',
                    'token_type',
                ],
            ]);
    }

    // ==========================================
    // 3. LOGIN TESTS
    // ==========================================

    public function test_user_can_login_with_valid_username_and_password(): void
    {
        $user = User::create([
            'username'          => 'validuser_' . Str::random(8),
            'email'             => 'valid_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('SecretPass123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'SecretPass123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
                'data'    => [
                    'token_type' => 'Bearer',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::create([
            'username'          => 'wrongpw_' . Str::random(8),
            'email'             => 'wrongpw_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('correct_password'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'incorrect_password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_user_cannot_login_with_non_existent_username(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'ghost_user_' . Str::random(10),
            'password' => 'anypassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_unverified_user_cannot_login(): void
    {
        $user = User::create([
            'username'          => 'unverified_' . Str::random(8),
            'email'             => 'unverified_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Your email address is not verified. Please verify your email before logging in.',
            ]);
    }

    public function test_successful_login_generates_valid_sanctum_token(): void
    {
        $user = User::create([
            'username'          => 'tokenuser_' . Str::random(8),
            'email'             => 'token_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password123',
        ]);

        $token = $response->json('data.token');
        $this->assertNotNull($token);

        // Verify the token works to fetch /api/user
        $userResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');

        $userResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'user' => [
                        'id'       => $user->id,
                        'username' => $user->username,
                        'email'    => $user->email,
                    ],
                ],
            ]);
    }

    // ==========================================
    // 4. PASSWORD RESET TESTS
    // ==========================================

    public function test_forgot_password_sends_email(): void
    {
        Notification::fake();

        $user = User::create([
            'username'          => 'forgotuser_' . Str::random(8),
            'email'             => 'forgot_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'If the account exists, a password reset link has been sent.',
            ]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_rejects_invalid_email_input(): void
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'invalid-email-format',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_reset_rejects_invalid_token(): void
    {
        $user = User::create([
            'username'          => 'badtokenuser_' . Str::random(8),
            'email'             => 'badtoken_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('oldpassword'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email'                 => $user->email,
            'token'                 => 'invalid_fake_reset_token',
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::create([
            'username'          => 'resetuser_' . Str::random(8),
            'email'             => 'reset_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('oldpassword123'),
            'email_verified_at' => now(),
        ]);

        // Generate token using Laravel password broker
        $token = Password::broker()->createToken($user);

        $newPassword = 'BrandNewPassword123!';
        $response = $this->postJson('/api/reset-password', [
            'email'                 => $user->email,
            'token'                 => $token,
            'password'              => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password has been reset successfully. Please log in with your new password.',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
    }

    public function test_password_reset_revokes_previous_sanctum_tokens(): void
    {
        $user = User::create([
            'username'          => 'tokenrevoke_' . Str::random(8),
            'email'             => 'revoke_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('oldpassword123'),
            'email_verified_at' => now(),
        ]);

        // Issue tokens
        $user->createToken('device_1');
        $user->createToken('device_2');
        $this->assertCount(2, $user->tokens);

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email'                 => $user->email,
            'token'                 => $token,
            'password'              => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertCount(0, $user->tokens, 'All personal access tokens must be revoked after password reset');
    }

    public function test_user_can_login_with_new_password_after_reset(): void
    {
        $user = User::create([
            'username'          => 'newpwlogin_' . Str::random(8),
            'email'             => 'newpw_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('initialpass123'),
            'email_verified_at' => now(),
        ]);

        $token = Password::broker()->createToken($user);
        $newPassword = 'BrandNewPassword123!';

        $this->postJson('/api/reset-password', [
            'email'                 => $user->email,
            'token'                 => $token,
            'password'              => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertStatus(200);

        // Attempt login with new password
        $loginResponse = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => $newPassword,
        ]);

        $loginResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ==========================================
    // 5. SANCTUM AUTHORIZATION & EXPIRATION TESTS
    // ==========================================

    public function test_protected_endpoint_rejects_unauthenticated_request(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_valid_sanctum_token_grants_access_to_protected_endpoint(): void
    {
        $user = User::create([
            'username'          => 'authed_' . Str::random(8),
            'email'             => 'authed_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'user' => [
                        'id'       => $user->id,
                        'username' => $user->username,
                    ],
                ],
            ]);
    }

    public function test_logout_revokes_current_sanctum_token(): void
    {
        $user = User::create([
            'username'          => 'logoutuser_' . Str::random(8),
            'email'             => 'logout_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $token1 = $user->createToken('device_1')->plainTextToken;
        $token2 = $user->createToken('device_2')->plainTextToken;

        $this->assertCount(2, $user->tokens);

        // Logout using token1
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        // Token1 should no longer work
        $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->getJson('/api/user')
            ->assertStatus(401);

        // Token2 should remain intact
        $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->getJson('/api/user')
            ->assertStatus(200);
    }

    public function test_expired_sanctum_token_cannot_access_protected_endpoint(): void
    {
        Config::set('sanctum.expiration', 10); // 10 minutes expiration

        $user = User::create([
            'username'          => 'expireuser_' . Str::random(8),
            'email'             => 'expire_' . Str::random(8) . '@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $newAccessToken = $user->createToken('expired_token');
        $plainToken = $newAccessToken->plainTextToken;

        // Manually age the token created_at past expiration (e.g. 20 minutes ago)
        PersonalAccessToken::findToken($plainToken)->update([
            'created_at' => Carbon::now()->subMinutes(20),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $plainToken)
            ->getJson('/api/user');

        $response->assertStatus(401);
    }
    */
}


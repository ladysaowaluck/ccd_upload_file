<?php

namespace Tests\Feature\Auth;

use App\Models\Auth\AdminUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

// Feature tests for email verification process (Register -> Verify -> Login)
class EmailVerificationTest extends TestCase
{
    use DatabaseTransactions;

    // Test that an unverified user cannot log in and is prompted to verify email first.
    public function test_unverified_user_cannot_login_and_is_blocked(): void
    {
        $password = 'Secret@1234';
        $user = AdminUser::create([
            'username' => 'unverified_' . Str::random(8),
            'name' => 'Unverified User',
            'email' => 'unverified_' . Str::random(8) . '@th.nissin-asia.com',
            'password' => Hash::make($password),
            'email_verified_at' => null,
            'email_token' => Str::random(64),
            'email_token_at' => now()->addHours(24),
        ]);

        $response = $this->post('/login', [
            'username' => $user->email,
            'password' => $password,
        ]);

        // User remains a guest (cannot access web app)
        $this->assertGuest();
        $response->assertSessionHasErrors('verification');
    }

    // Test that user can verify email address by clicking the verification token link.
    public function test_user_can_verify_email_via_token_link(): void
    {
        $token = Str::random(64);
        $user = AdminUser::create([
            'username' => 'toverify_' . Str::random(8),
            'name' => 'To Verify User',
            'email' => 'toverify_' . Str::random(8) . '@th.nissin-asia.com',
            'password' => Hash::make('Secret@1234'),
            'email_verified_at' => null,
            'email_token' => $token,
            'email_token_at' => now()->addHours(24),
        ]);

        $response = $this->get('/api/email/verify/' . $token);

        // Redirects to login with verified success message
        $response->assertRedirect('/login');
        $response->assertSessionHas('verified', 'Your email has been verified! You can now sign in.');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->email_token);
    }

    // Test that invalid verification token is rejected.
    public function test_invalid_verification_token_is_rejected(): void
    {
        $response = $this->get('/api/email/verify/invalid_token_12345');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('verification');
    }
}


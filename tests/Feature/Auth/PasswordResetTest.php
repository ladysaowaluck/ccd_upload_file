<?php

namespace Tests\Feature\Auth;

use App\Models\Auth\AdminUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

// Feature tests for self-service password reset process matching present process
class PasswordResetTest extends TestCase
{
    use DatabaseTransactions;

    // Test that forgot-password screen can be rendered.
    
    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Forgot Password');
        $response->assertSee('Corporate Email');
    }

    // Test that password reset link can be requested for valid corporate email.
    public function test_password_reset_link_can_be_requested(): void
    {
        $email = 'reset_' . Str::random(8) . '@th.nissin-asia.com';
        AdminUser::create([
            'username' => 'resetuser_' . Str::random(8),
            'name' => 'Reset Test User',
            'email' => $email,
            'password' => Hash::make('Password@123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/forgot-password', [
            'email' => $email,
        ]);

        $response->assertSessionHas('status');
    }

    // Test that reset password form screen can be rendered with valid query parameters.
    public function test_reset_password_screen_can_be_rendered_with_token_and_email(): void
    {
        $email = 'resetform_' . Str::random(8) . '@th.nissin-asia.com';
        $token = Str::random(64);

        $response = $this->get("/reset-password?token={$token}&email={$email}");

        $response->assertStatus(200);
        $response->assertSee('Set New Password');
    }
}


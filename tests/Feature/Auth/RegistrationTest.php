<?php

namespace Tests\Feature\Auth;

use App\Mail\VerificationEmail;
use App\Models\Auth\AdminUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

// Feature tests for registration process matching present business flow
class RegistrationTest extends TestCase
{
    use DatabaseTransactions;

    // Test that the employee registration screen can be rendered.
    
    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Employee Self-Registration');
        $response->assertSee('@th.nissin-asia.com');
    }

    // Test that new users can register with valid corporate email, receive verification email, and are prompted to verify.
    public function test_new_users_can_register_and_must_verify_email(): void
    {
        Mail::fake();

        $username = 'emp_' . Str::random(8);
        $email = $username . '@th.nissin-asia.com';

        $response = $this->post('/register', [
            'name' => 'Test Employee',
            'username' => $username,
            'email' => $email,
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        // User is redirected to login with explicit verification prompt
        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'Registration successful. Please check your email to verify your account.');

        // User is NOT automatically logged in (must click email verification link first)
        $this->assertGuest();

        // User record exists with pending email verification
        $user = AdminUser::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email_token);

        // Verification email dispatched
        Mail::assertSent(VerificationEmail::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });
    }

    // Test that registration rejects non-company domain email addresses.
    
    public function test_registration_rejects_non_company_domain(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'External User',
            'username' => 'external_' . Str::random(6),
            'email' => 'external@gmail.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        Mail::assertNothingSent();
    }
}


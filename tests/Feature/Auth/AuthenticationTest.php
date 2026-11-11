<?php

namespace Tests\Feature\Auth;

use App\Models\Auth\AdminUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

// Gemini : Feature tests for web session authentication matching present process
class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test that login screen can be rendered.
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign In');
        $response->assertSee('Corporate Email');
    }

    /**
     * Test that verified users can authenticate using corporate email and redirect to clearance.
     */
    public function test_verified_user_can_authenticate_using_corporate_email(): void
    {
        $password = 'Correct@1234';
        $user = AdminUser::create([
            'username' => 'authuser_' . Str::random(8),
            'name' => 'Authenticated User',
            'email' => 'authuser_' . Str::random(8) . '@th.nissin-asia.com',
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'username' => $user->email,
            'password' => $password,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/clearance');
    }

    /**
     * Test that user can authenticate using username as well.
     */
    public function test_verified_user_can_authenticate_using_username(): void
    {
        $password = 'Correct@1234';
        $user = AdminUser::create([
            'username' => 'userlogin_' . Str::random(8),
            'name' => 'User By Username',
            'email' => 'userlogin_' . Str::random(8) . '@th.nissin-asia.com',
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => $password,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/clearance');
    }

    /**
     * Test that users cannot authenticate with invalid password.
     */
    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = AdminUser::create([
            'username' => 'badpass_' . Str::random(8),
            'name' => 'Bad Password User',
            'email' => 'badpass_' . Str::random(8) . '@th.nissin-asia.com',
            'password' => Hash::make('CorrectPassword123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'username' => $user->email,
            'password' => 'WrongPassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    /**
     * Test that authenticated users can log out.
     */
    public function test_user_can_logout(): void
    {
        $user = AdminUser::create([
            'username' => 'logout_' . Str::random(8),
            'name' => 'Logout User',
            'email' => 'logout_' . Str::random(8) . '@th.nissin-asia.com',
            'password' => Hash::make('Password@123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}


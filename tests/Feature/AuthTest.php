<?php

namespace Tests\Feature;

use App\Models\User;
use App\Helpers\JwtHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmailMail;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_verification_mail_is_sent(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123'
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message', 'user' => ['id', 'name', 'email']
        ]);
        $response->assertJsonFragment([
            'message' => 'User registered successfully. Please check your email to verify your account.'
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
            'email_verified_at' => null
        ]);

        $user = User::where('email', 'alice@example.com')->first();
        $this->assertNotNull($user->verification_token);

        Mail::assertSent(VerifyEmailMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && str_contains($mail->verificationUrl, $user->verification_token);
        });
    }

    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123'
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_login_when_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'bob@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'bob@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message', 'user', 'token', 'expires_in'
        ]);
    }

    public function test_user_cannot_login_if_unverified(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'bob@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'bob@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'email_unverified' => true,
            'email' => 'bob@example.com'
        ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'bob@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'bob@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_verify_email(): void
    {
        $user = User::factory()->unverified()->create([
            'verification_token' => 'test-token-12345'
        ]);

        $this->assertNull($user->email_verified_at);

        $response = $this->get('/api/verify-email?token=test-token-12345');

        // Should redirect to frontend login with verified success flag
        $response->assertRedirect('http://localhost:5173/?verified=true');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->verification_token);
    }

    public function test_user_cannot_verify_with_invalid_token(): void
    {
        $response = $this->get('/api/verify-email?token=nonexistent-token');
        $response->assertRedirect('http://localhost:5173/?verification_error=invalid_token');
    }

    public function test_user_can_resend_verification_email(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.com'
        ]);

        $response = $this->postJson('/api/resend-verification', [
            'email' => 'unverified@example.com'
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => 'Verification email resent successfully! Please check your inbox.'
        ]);

        Mail::assertSent(VerifyEmailMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_user_can_get_profile(): void
    {
        $user = User::factory()->create();
        $token = JwtHelper::generateToken($user->id, 5);

        $response = $this->withToken($token)->getJson('/api/me');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $user->id,
            'email' => $user->email
        ]);
    }

    public function test_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $token = JwtHelper::generateToken($user->id, 5);

        $response = $this->withToken($token)->postJson('/api/refresh');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message', 'token', 'expires_in'
        ]);
    }
}

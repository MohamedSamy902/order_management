<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /** Registration Tests */

    public function test_user_can_register_with_valid_data(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'My$3cur3P@ssw0rd!',
            'password_confirmation' => 'My$3cur3P@ssw0rd!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['email', 'message', 'otp_expires_in']
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'My$3cur3P@ssw0rd!',
            'password_confirmation' => 'My$3cur3P@ssw0rd!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_with_weak_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'My$3cur3P@ssw0rd!',
            'password_confirmation' => 'My$3cur3P@ssw0rd!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_sends_otp_email(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'My$3cur3P@ssw0rd!',
            'password_confirmation' => 'My$3cur3P@ssw0rd!',
        ]);

        Mail::assertSent(\App\Mail\OtpMail::class);
    }

    public function test_registration_respects_rate_limit(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/register', [
                'name' => 'Test User ' . $i,
                'email' => 'test' . $i . '@example.com',
                'password' => 'My$3cur3P@ssw0rd!',
                'password_confirmation' => 'My$3cur3P@ssw0rd!',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User Limit',
            'email' => 'limit@example.com',
            'password' => 'My$3cur3P@ssw0rd!',
            'password_confirmation' => 'My$3cur3P@ssw0rd!',
        ]);

        $response->assertStatus(400);
    }

    /** Login Tests */

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('My$3cur3P@ssw0rd!'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'My$3cur3P@ssw0rd!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user'
                ]
            ]);

        $this->assertEquals('bearer', $response->json('data.token_type'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('My$3cur3P@ssw0rd!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_login_returns_jwt_token(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('My$3cur3P@ssw0rd!'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'My$3cur3P@ssw0rd!',
        ]);

        $token = $response->json('data.access_token');

        $this->assertNotNull($token);
        $this->assertIsString($token);
    }

    public function test_login_respects_rate_limit(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('My$3cur3P@ssw0rd!'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'WrongPassword',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'My$3cur3P@ssw0rd!',
        ]);

        $response->assertStatus(400);
    }

    /** Token Management Tests */

    public function test_user_can_refresh_token(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in'
                ]
            ]);

        $this->assertNotEquals($token, $response->json('data.access_token'));
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_refresh_requires_valid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/api/v1/auth/refresh');

        $response->assertStatus(401);
    }

    /** User Profile Tests */

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ]
            ]);
    }

    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }
}

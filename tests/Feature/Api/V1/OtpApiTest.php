<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpApiTest extends TestCase
{
    use RefreshDatabase;

    /** OTP Verification Tests */

    public function test_user_can_verify_otp_successfully(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $otp = $user->generateOtp();

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp' => $otp,
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
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verify_otp_fails_with_invalid_code(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->generateOtp();

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp' => '000000',
        ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertEquals(1, $user->fresh()->otpVerification->attempts);
    }

    public function test_verify_otp_fails_with_expired_code(): void
    {
        config(['auth.otp.expiry' => 0]);

        $user = User::factory()->create(['email_verified_at' => null]);
        $otp = $user->generateOtp();

        sleep(1);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp' => $otp,
        ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verify_otp_fails_after_max_attempts(): void
    {
        config(['auth.otp.max_attempts' => 3]);

        $user = User::factory()->create(['email_verified_at' => null]);
        $user->generateOtp();
        $user->update(['otp_attempts' => 3]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp' => '123456',
        ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_verify_otp_returns_jwt_token(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $otp = $user->generateOtp();

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp' => $otp,
        ]);

        $token = $response->json('data.access_token');

        $this->assertNotNull($token);
        $this->assertIsString($token);
        $this->assertGreaterThan(20, strlen($token));
    }

    public function test_verify_otp_clears_otp_data_on_success(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $otp = $user->generateOtp();

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'otp' => $otp,
        ]);

        $user->refresh();
        $this->assertNull($user->otp_code);
        $this->assertNull($user->otp_expires_at);
        $this->assertEquals(0, $user->otp_attempts);
    }

    public function test_verify_otp_fails_with_missing_email(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'otp' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_verify_otp_fails_with_missing_otp(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);
    }

    /** OTP Resend Tests */

    public function test_user_can_resend_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $oldOtp = $user->generateOtp();

        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['email', 'otp_expires_in']
            ]);

        $this->assertNotEquals($oldOtp, $user->fresh()->otp_code);
        Mail::assertSent(\App\Mail\OtpMail::class);
    }

    public function test_resend_otp_fails_for_verified_email(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'email' => $user->email,
        ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_resend_otp_sends_new_code(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $user->generateOtp();

        $this->postJson('/api/v1/auth/resend-otp', [
            'email' => $user->email,
        ]);

        Mail::assertSent(\App\Mail\OtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_resend_otp_respects_rate_limit(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/resend-otp', [
                'email' => $user->email,
            ]);
        }

        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'email' => $user->email,
        ]);

        $response->assertStatus(400);
    }

    public function test_resend_otp_fails_with_missing_email(): void
    {
        $response = $this->postJson('/api/v1/auth/resend-otp', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_resend_otp_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}

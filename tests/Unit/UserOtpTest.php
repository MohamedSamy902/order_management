<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_otp_creates_6_digit_code(): void
    {
        $user = User::factory()->create();

        $otp = $user->generateOtp();

        $this->assertEquals(6, strlen($otp));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);
        $this->assertEquals($otp, $user->otpVerification->code);
    }

    public function test_otp_expires_after_configured_time(): void
    {
        config(['auth.otp.expiry' => 10]);

        $user = User::factory()->create();
        $user->generateOtp();

        $verification = $user->otpVerification;
        $this->assertNotNull($verification->expires_at);
        $this->assertTrue(
            $verification->expires_at->diffInMinutes(now()) <= 10
        );
    }

    public function test_is_otp_valid_returns_true_for_valid_otp(): void
    {
        $user = User::factory()->create();
        $otp = $user->generateOtp();

        $this->assertTrue($user->isOtpValid($otp));
    }

    public function test_is_otp_valid_returns_false_for_invalid_otp(): void
    {
        $user = User::factory()->create();
        $user->generateOtp();

        $this->assertFalse($user->isOtpValid('000000'));
    }

    public function test_is_otp_valid_returns_false_for_expired_otp(): void
    {
        config(['auth.otp.expiry' => 0]);

        $user = User::factory()->create();
        $otp = $user->generateOtp();

        sleep(1);

        $this->assertFalse($user->fresh()->isOtpValid($otp));
    }

    public function test_is_otp_expired_returns_true_when_expired(): void
    {
        $user = User::factory()->create();
        $user->otpVerification()->create([
            'code' => '123456',
            'expires_at' => now()->subMinutes(5),
            'attempts' => 0
        ]);

        $this->assertTrue($user->isOtpExpired());
    }

    public function test_has_reached_max_attempts_returns_true_at_limit(): void
    {
        config(['auth.otp.max_attempts' => 5]);

        $user = User::factory()->create();
        $user->otpVerification()->create([
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 5
        ]);

        $this->assertTrue($user->hasReachedMaxOtpAttempts());
    }

    public function test_has_reached_max_attempts_returns_false_below_limit(): void
    {
        config(['auth.otp.max_attempts' => 5]);

        $user = User::factory()->create();
        $user->otpVerification()->create([
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 3
        ]);

        $this->assertFalse($user->hasReachedMaxOtpAttempts());
    }

    public function test_clear_otp_resets_all_fields(): void
    {
        $user = User::factory()->create();
        $user->otpVerification()->create([
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 3
        ]);

        $user->clearOtp();

        $user->refresh();
        $this->assertNull($user->otpVerification);
    }

    public function test_otp_attempts_increments_correctly(): void
    {
        $user = User::factory()->create();
        $user->otpVerification()->create([
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 2
        ]);

        $user->incrementOtpAttempts();

        $this->assertEquals(3, $user->otpVerification->fresh()->attempts);
    }
}

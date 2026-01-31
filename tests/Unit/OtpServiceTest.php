<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\OtpService;
use App\Mail\OtpMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->otpService = app(OtpService::class);
    }

    public function test_send_otp_generates_valid_6_digit_code(): void
    {
        $user = User::factory()->create();

        Mail::fake();

        $otp = $this->otpService->sendOtpUser($user);

        $this->assertEquals(6, strlen($otp));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);
        Mail::assertSent(OtpMail::class);
    }

    public function test_verify_accepts_valid_otp(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $otp = $this->otpService->sendOtpUser($user);

        $result = $this->otpService->verify($user, $otp);

        $this->assertTrue($result);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->otpVerification);
    }

    public function test_verify_rejects_invalid_otp(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->otpService->sendOtpUser($user);

        $result = $this->otpService->verify($user, '000000');

        $this->assertFalse($result);
        $this->assertEquals(1, $user->fresh()->otpVerification->attempts);
    }

    public function test_verify_rejects_expired_otp(): void
    {
        config(['auth.otp.expiry' => 0]);
        Mail::fake();

        $user = User::factory()->create();
        $otp = $this->otpService->sendOtpUser($user);

        sleep(1);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OTP has expired');

        $this->otpService->verify($user, $otp);
    }

    public function test_verify_throws_exception_when_max_attempts_reached(): void
    {
        config(['auth.otp.max_attempts' => 3]);
        Mail::fake();

        $user = User::factory()->create();
        $this->otpService->sendOtpUser($user);
        $user->otpVerification()->update(['attempts' => 3]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Maximum OTP attempts reached');

        $this->otpService->verify($user, '123456');
    }

    public function test_verify_clears_otp_on_success(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $otp = $this->otpService->sendOtpUser($user);

        $this->otpService->verify($user, $otp);

        $this->assertNull($user->fresh()->otpVerification);
    }

    public function test_resend_throws_exception_for_verified_email(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email already verified');

        $this->otpService->resend($user);
    }

    public function test_resend_generates_new_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $this->otpService->sendOtpUser($user);

        $newOtp = $this->otpService->resend($user);

        // In testing environment, OTP is always 111111
        $this->assertEquals('111111', $newOtp);
        $this->assertEquals($newOtp, $user->fresh()->otpVerification->code);
        Mail::assertSent(OtpMail::class);
    }
}

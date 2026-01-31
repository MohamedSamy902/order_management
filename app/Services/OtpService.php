<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function sendOtpUser(User $user): string
    {
        $otp = config('app.otp_env') == 'local'
            ? '111111'
            : str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete old OTP if exists
        $user->otpVerification()?->delete();

        // Create new OTP
        $user->otpVerification()->create([
            'code' => $otp,
            'expires_at' => now()->addMinutes((int) config('auth.otp.expiry', 10)),
            'attempts' => 0,
        ]);

        Mail::to($user->email)->send(new OtpMail($otp, $user->name));

        return $otp;
    }

    public function verify(User $user, string $otp): bool
    {
        $otpRecord = $user->otpVerification;

        if (!$otpRecord) {
            throw new \Exception('No OTP found. Please request a new one.');
        }

        if ($otpRecord->hasReachedMaxAttempts()) {
            throw new \Exception('Maximum OTP attempts reached. Please request a new OTP.');
        }

        if ($otpRecord->isExpired()) {
            throw new \Exception('OTP has expired. Please request a new one.');
        }

        if (!$otpRecord->isValid($otp)) {
            $otpRecord->increment('attempts');
            return false;
        }

        // Success - mark email as verified and delete OTP
        $user->update(['email_verified_at' => now()]);
        $otpRecord->delete();

        return true;
    }

    public function resend(User $user): string
    {
        if ($user->hasVerifiedEmail()) {
            throw new \Exception('Email already verified.');
        }

        return $this->sendOtpUser($user);
    }
}

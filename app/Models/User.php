<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\Model\HasImage;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasImage;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }


    public function otpVerification()
    {
        return $this->hasOne(OtpVerification::class)->latestOfMany();
    }

    public function otpVerifications()
    {
        return $this->hasMany(OtpVerification::class);
    }

    /**
     * Generate a new OTP code
     */
    public function generateOtp(): string
    {
        $otp = (string) random_int(100000, 999999);
        $expiry = config('auth.otp.expiry', 10); // Default 10 minutes

        $this->otpVerifications()->create([
            'code' => $otp,
            'expires_at' => now()->addMinutes($expiry),
            'attempts' => 0
        ]);

        return $otp;
    }

    /**
     * Check if OTP is valid
     */
    public function isOtpValid(string $otp): bool
    {
        $verification = $this->otpVerification;

        if (!$verification) {
            return false;
        }

        if ($verification->code !== $otp) {
            $this->incrementOtpAttempts();
            return false;
        }

        if ($this->isOtpExpired()) {
            return false;
        }

        return true;
    }

    /**
     * Check if OTP is expired
     */
    public function isOtpExpired(): bool
    {
        $verification = $this->otpVerification;
        return $verification && $verification->expires_at->isPast();
    }

    /**
     * Check if max attempts reached
     */
    public function hasReachedMaxOtpAttempts(): bool
    {
        $maxAttempts = config('auth.otp.max_attempts', 5);
        $verification = $this->otpVerification;

        return $verification && $verification->attempts >= $maxAttempts;
    }

    /**
     * Clear OTP data
     */
    public function clearOtp(): void
    {
        $this->otpVerification()->delete();
    }

    /**
     * Increment OTP attempts
     */
    public function incrementOtpAttempts(): void
    {
        $verification = $this->otpVerification;
        if ($verification) {
            $verification->increment('attempts');
        }
    }
}

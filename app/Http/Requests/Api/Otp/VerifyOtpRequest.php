<?php

namespace App\Http\Requests\Api\Otp;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists'  => __('No account found with this email address.'),
            'otp.size'      => __('OTP must be exactly 6 digits.'),
        ];
    }
}

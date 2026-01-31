<?php

namespace App\Http\Requests\Api\Profile;

use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'lowercase', 'email', 'max:200', 'unique:users,email,' . $userId],
            'password' => [
                'sometimes',
                'string',
                'min:8',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'image' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => __('Name must be a string'),
            'name.max' => __('Name must not exceed 255 characters'),
            'email.email' => __('Email must be a valid email address'),
            'email.unique' => __('Email already exists'),
            'password.min' => __('Password must be at least 8 characters'),
            'password.confirmed' => __('Password confirmation does not match'),
            'image.image' => __('File must be an image'),
            'image.max' => __('Image size must not exceed 2MB'),
        ];
    }
}

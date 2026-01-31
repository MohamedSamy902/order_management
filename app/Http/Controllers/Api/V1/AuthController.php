<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Helpers\ApiResponse;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Transformers\API\V1\UserTransformer;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Otp\ResendOtpRequest;
use App\Http\Requests\Api\Otp\VerifyOtpRequest;

class AuthController extends Controller
{

    public function __construct(
        private OtpService $otpService
    ) {}
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $this->otpService->sendOtpUser($user);

        return ApiResponse::created([
            'email' => $user->email,
            'message' => __('Registration successful. Please check your email for OTP verification.'),
            'otp_expires_in' => config('auth.otp.expiry') * 60, // seconds
        ], __('Please verify your email to complete registration'));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!$token = auth('api')->attempt($request->validated())) {
            return ApiResponse::unauthorized(__('Invalid credentials'));
        }

        return ApiResponse::success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => (new UserTransformer())->transform(auth()->user()),
        ], 'Login successful');
    }


    public function logout(): JsonResponse
    {
        auth()->logout();
        return ApiResponse::success(null, 'Successfully logged out');
    }

    public function refresh(): JsonResponse
    {
        $token = auth()->refresh();

        return ApiResponse::success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ], 'Token refreshed successfully');
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->firstOrFail();

        try {
            if (!$this->otpService->verify($user, $request->otp)) {
                return ApiResponse::badRequest('Invalid OTP code');
            }

            // Login user after successful verification
            $token = auth('api')->login($user);

            return ApiResponse::success([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'user' => $user,
            ], __('OTP verified successfully'));
        } catch (\Exception $e) {
            return ApiResponse::badRequest($e->getMessage());
        }
    }

    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->firstOrFail();

        try {
            $this->otpService->resend($user);

            return ApiResponse::success([
                'email' => $user->email,
                'otp_expires_in' => config('auth.otp.expiry') * 60,
            ], __('OTP has been resent to your email.'));
        } catch (\Exception $e) {
            return ApiResponse::badRequest($e->getMessage());
        }
    }
}

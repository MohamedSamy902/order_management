<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Services\ImageService;
use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Transformers\API\V1\UserTransformer;
use App\Http\Requests\Api\Profile\UpdateProfileRequest;
use App\Services\ProfileService;

class ProfileController extends Controller
{
    protected ImageService $imageService;
    protected ProfileService $profileService;

    public function __construct(ImageService $imageService, ProfileService $profileService)
    {
        $this->imageService = $imageService;
        $this->profileService = $profileService;
    }

    public function show(): JsonResponse
    {
        $user = auth()->user();

        return ApiResponse::success((new UserTransformer())->transform($user), __('Profile retrieved successfully'));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();

        try {
            $user = $this->profileService->update($user, $request->validated(), $request->file('image'));

            return ApiResponse::success((new UserTransformer())->transform($user), __('Profile updated successfully'));
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}

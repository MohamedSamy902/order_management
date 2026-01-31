<?php
namespace App\Services;

use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function __construct(
        protected ImageService $imageService
    ) {}

    public function update(User $user, array $data, ?UploadedFile $image = null): User
    {
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        if (isset($data['password'])) {
            $user->password = $data['password'];
        }

        if ($image) {
            $user->image = $this->imageService->updateImage(
                $image,
                $user->image,
                'users'
            );
        }

        $user->save();

        return $user->fresh();
    }
}

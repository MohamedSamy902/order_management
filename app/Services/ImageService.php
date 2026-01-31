<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MohamedSamy902\AdvancedFileUpload\Services\FileUploadService;

class ImageService
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Upload an image and return the path
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return string|null
     * @throws Exception
     */
    public function upload(UploadedFile $file, string $folder = 'images'): ?string
    {
        try {
            $result = $this->fileUploadService->upload($file, [
                'folder_name' => $folder,
            ]);

            if (!isset($result['status']) || $result['status'] !== true) {
                throw new Exception('Image upload failed: ' . ($result['error'] ?? 'Unknown error'));
            }

            return $result['path'] ?? null;
        } catch (Exception $e) {
            Log::error('Image upload failed', [
                'folder' => $folder,
                'error' => $e->getMessage(),
            ]);
            throw new Exception(__('Failed to upload image: ') . $e->getMessage());
        }
    }

    /**
     * Delete an image by path
     *
     * @param string|null $imagePath
     * @return bool
     */
    public function delete(?string $imagePath): bool
    {
        if (!$imagePath) {
            return false;
        }

        try {
            $this->fileUploadService->delete($imagePath);
            return true;
        } catch (Exception $e) {
            Log::error('Image deletion failed', [
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete image thumbnails
     *
     * @param string $imagePath
     * @return void
     */
    protected function deleteThumbnails(string $imagePath): void
    {
        try {
            $directory = dirname($imagePath);
            $filename = basename($imagePath);

            $sizes = ['thumb_small_', 'thumb_medium_', 'thumb_large_'];

            foreach ($sizes as $prefix) {
                $thumbnailPath = $directory . '/' . $prefix . $filename;
                if (Storage::disk('public')->exists($thumbnailPath)) {
                    Storage::disk('public')->delete($thumbnailPath);
                }
            }
        } catch (Exception $e) {
            Log::warning('Thumbnail deletion failed', [
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update image - delete old and
     */
    public function updateImage(UploadedFile $file, ?string $oldImagePath, string $folder = 'images'): ?string
    {
        $newPath = $this->upload($file, $folder);

        if ($newPath && $oldImagePath) {
            $this->delete($oldImagePath);
        }

        return $newPath;
    }
}

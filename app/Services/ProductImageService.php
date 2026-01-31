<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ImageProduct;
use Illuminate\Support\Facades\Log;

class ProductImageService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function syncImages(Product $product, ?array $images = null, array $deletedImages = []): void
    {
        if (!empty($deletedImages)) {
            $this->deleteImages($product, $deletedImages);
        }

        if ($images) {
            $this->attachImages($product, $images);
        }
    }

    public function attachImages(Product $product, array $images): void
    {
        foreach ($images as $imageFile) {
            try {
                $imagePath = $this->imageService->upload($imageFile, 'products');

                ImageProduct::create([
                    'product_id' => $product->id,
                    'image' => $imagePath,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to upload product image', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    public function deleteImages(Product $product, array $imageIds): void
    {
        $images = $product->images()->whereIn('id', $imageIds)->get();

        foreach ($images as $image) {
            $this->imageService->delete($image->image);
            $image->delete();
        }
    }

    public function deleteAllImages(Product $product): void
    {
        foreach ($product->images as $image) {
            $this->imageService->delete($image->image);
        }

        $product->images()->delete();
    }
}

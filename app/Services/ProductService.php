<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use App\Filters\Product\StatusFilter;
use App\Filters\Product\SearchFilter;
use App\Filters\Product\InStockFilter;

class ProductService
{
    protected ProductImageService $productImageService;

    public function __construct(ProductImageService $productImageService)
    {
        $this->productImageService = $productImageService;
    }

    public function getAllProducts(array $filters = [])
    {
        $query = Product::with('images');

        $query = app(Pipeline::class)
            ->send($query)
            ->through([
                new StatusFilter($filters['status'] ?? null),
                new SearchFilter($filters['search'] ?? null),
                new InStockFilter($filters['in_stock'] ?? null),
            ])
            ->thenReturn();

        $perPage = $filters['per_page'] ?? 15;

        return $query->latest()->paginate($perPage);
    }

    public function getProductById(int $id): Product
    {
        return Product::with('images')->findOrFail($id);
    }

    public function createProduct(array $data, ?array $images = null): Product
    {
        return DB::transaction(function () use ($data, $images) {
            $product = Product::create([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'price' => $data['price'] ?? 0,
                'stock' => $data['stock'] ?? 0,
                'status' => $data['status'] ?? 'active',
            ]);

            if ($images) {
                $this->productImageService->attachImages($product, $images);
            }

            return $product->load('images');
        });
    }

    public function updateProduct(int $id, array $data, ?array $images = null, array $deletedImages = []): Product
    {
        return DB::transaction(function () use ($id, $data, $images, $deletedImages) {
            $product = Product::findOrFail($id);
            $product->update($data);

            $this->productImageService->syncImages($product, $images, $deletedImages);

            return $product->fresh(['images']);
        });
    }

    public function deleteProduct(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $product = Product::findOrFail($id);

            $this->productImageService->deleteAllImages($product);

            return $product->delete();
        });
    }
}

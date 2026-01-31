<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Helpers\ApiResponse;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Transformers\API\V1\ProductTransformer;
use App\Http\Requests\Api\Product\ProductRequest;
use App\Http\Requests\Api\Product\ProductFilterRequest;

class ProductController extends Controller
{
    protected ProductService $productService;
    protected ProductTransformer $transformer;

    public function __construct(ProductService $productService, ProductTransformer $transformer)
    {
        $this->productService = $productService;
        $this->transformer = $transformer;
    }

    public function index(ProductFilterRequest $request): JsonResponse
    {
        $products = $this->productService->getAllProducts($request->validated());

        return ApiResponse::success(
            $this->transformer->transformPaginate($products),
            __('Products retrieved successfully')
        );
    }

    public function store(ProductRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $images = $request->hasFile('images') ? $request->file('images') : null;

            $product = $this->productService->createProduct($data, $images);

            return ApiResponse::created(
                $this->transformer->transform($product),
                __('Product created successfully')
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                __('Failed to create product: ') . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productService->getProductById($id);

            return ApiResponse::success(
                $this->transformer->transform($product),
                __('Product retrieved successfully')
            );
        } catch (Exception $e) {
            return ApiResponse::error(__('Product not found'), null, 404);
        }
    }

    public function update(ProductRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $images = $request->hasFile('images') ? $request->file('images') : null;
            $deletedImages = $request->input('deleted_images', []);

            $product = $this->productService->updateProduct($id, $data, $images, $deletedImages);

            return ApiResponse::success(
                $this->transformer->transform($product),
                __('Product updated successfully')
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                __('Failed to update product: ') . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->productService->deleteProduct($id);

            return ApiResponse::success(null, __('Product deleted successfully'));
        } catch (Exception $e) {
            return ApiResponse::error(
                __('Failed to delete product: ') . $e->getMessage(),
                null,
                500
            );
        }
    }
}

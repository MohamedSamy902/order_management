<?php

namespace App\Transformers\API\V1;

use App\Transformers\BaseTransformer;
use App\Transformers\API\ImageTransformer;

class ProductTransformer extends BaseTransformer
{
    public function transform($product): array
    {
        return [
            'id'            => $product->id,
            'name'          => $product->name,
            'description'   => $product->description,
            'price'         => (float) $product->price,
            'stock'         => $product->stock,
            'status'        => $product->status,
            'images' => (new ImageTransformer())->transformCollection($product->images),

            // 'images' => $product->images->map(function ($image) {
            //     return [
            //         'id' => $image->id,
            //         'url' => $image->image,
            //         'thumbnail' => [
            //             'small' => $image->image_small,
            //             'medium' => $image->image_medium,
            //             'large' => $image->image_large,
            //         ],
            //     ];
            // }),
            // 'created_at' => $product->created_at?->toISOString(),
            // 'updated_at' => $product->updated_at?->toISOString(),
        ];
    }

    public function collection($products)
    {
        return $products->map(fn($product) => $this->transform($product));
    }
}

<?php

namespace App\Transformers\API\V1;

use App\Transformers\BaseTransformer;

class OrderItemTransformer extends BaseTransformer
{
    public function transform($orderItem): array
    {
        if (!$orderItem) {
            return [];
        }

        return [
            'id' => $orderItem->id,
            'product' => $orderItem->product ? (new ProductTransformer())->transform($orderItem->product) : null,
            'quantity' => $orderItem->quantity,
            'unit_price' => (float) $orderItem->unit_price,
            'total_price' => (float) $orderItem->total_price,
        ];
    }
}

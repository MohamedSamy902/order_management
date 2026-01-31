<?php

namespace App\Transformers\API\V1;

use App\Transformers\BaseTransformer;

class OrderTransformer extends BaseTransformer
{
    public function transform($order): array
    {
        if (!$order) {
            return [];
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'subtotal' => (float) $order->subtotal,
            'tax' => (float) $order->tax,
            'shipping' => (float) $order->shipping,
            'discount' => (float) $order->discount,
            'total' => (float) $order->total,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
            'notes' => $order->notes,
            'billing_address' => $order->billing_address,
            'shipping_address' => $order->shipping_address,
            'items' => (new OrderItemTransformer())->transformCollection($order->items ?? []),
            'payments' => (new PaymentTransformer())->transformCollection($order->payments ?? []),
            'user' => $order->user ? (new UserTransformer())->transform($order->user) : null,
            'created_at' => $order->created_at?->toISOString(),
            'updated_at' => $order->updated_at?->toISOString(),
        ];
    }
}

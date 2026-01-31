<?php

namespace App\Transformers\API\V1;

use App\Transformers\BaseTransformer;

class PaymentTransformer extends BaseTransformer
{
    public function transform($payment): array
    {
        if (!$payment) {
            return [];
        }

        return [
            'id' => $payment->id,
            'payment_id' => $payment->payment_id,
            'gateway' => $payment->gateway,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'paid_at' => $payment->paid_at?->toISOString(),
            'created_at' => $payment->created_at?->toISOString(),
            'order' => $payment->order ? [
                'id' => $payment->order->id,
                'order_number' => $payment->order->order_number,
                'total' => (float) $payment->order->total,
                'order_status' => $payment->order->order_status,
            ] : null,
        ];
    }
}

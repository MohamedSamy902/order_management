<?php

namespace App\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;

class TabbyGateway extends BasePaymentGateway
{
    protected $publicKey;
    protected $merchantCode;

    public function __construct()
    {
        $this->testMode = config('payment.gateways.tabby.test_mode');
        $this->publicKey = config('payment.gateways.tabby.public_key');
        $this->apiKey = config('payment.gateways.tabby.secret_key'); // Secret key
        $this->merchantCode = config('payment.gateways.tabby.merchant_code');
        $this->apiURL = 'https://api.tabby.ai/api/v2';
        $this->currency = config('payment.gateways.tabby.currency', 'SAR');
    }

    public function createCheckoutSession(Order $order): array
    {
        $user = $order->user;
        $items = $this->prepareOrderItems($order);

        $payload = [
            'payment' => [
                'amount' => number_format($order->total, 2, '.', ''),
                'currency' => $this->currency,
                'description' => 'Order #' . $order->order_number,
                'buyer' => [
                    'phone' => $user->user_phone ?? '500000001',
                    'email' => $user->email ?? 'customer@example.com',
                    'name' => $user->name,
                    'dob' => $user->dob ?? '1990-01-01',
                ],
                'shipping_address' => $this->formatAddress($order->shipping_address ?? $order->billing_address),
                'order' => [
                    'tax_amount' => number_format($order->tax, 2, '.', ''),
                    'shipping_amount' => number_format($order->shipping, 2, '.', ''),
                    'discount_amount' => number_format($order->discount, 2, '.', ''),
                    'updated_at' => now()->toISOString(),
                    'reference_id' => $order->order_number,
                    'items' => $items,
                ],
                'buyer_history' => [
                    'registered_since' => $user->created_at->toISOString(),
                    'loyalty_level' => 0,
                    'wishlist_count' => 0,
                    'is_phone_number_verified' => !empty($user->user_phone),
                    'is_email_verified' => !empty($user->email_verified_at),
                ],
            ],
            'lang' => app()->getLocale() === 'ar' ? 'ar' : 'en',
            'merchant_code' => $this->merchantCode,
            'merchant_urls' => [
                'success' => config('payment.gateways.tabby.callback_url') . '?gateway=tabby&status=success',
                'cancel' => config('payment.gateways.tabby.callback_url') . '?gateway=tabby&status=cancel',
                'failure' => config('payment.gateways.tabby.callback_url') . '?gateway=tabby&status=failure',
            ],
        ];

        $response = $this->makeRequest('/checkout', $payload);

        if (isset($response['status']) && $response['status'] === 'rejected') {
            throw new \Exception('Tabby rejected the payment request');
        }

        return [
            'payment_url' => $response['configuration']['available_products']['installments'][0]['web_url'] ?? null,
            'payment_id' => $response['id'] ?? null,
        ];
    }

    public function verifyPayment(string $paymentId): bool
    {
        $response = $this->makeRequest("/payments/{$paymentId}", [], 'GET');

        return isset($response['status']) && $response['status'] === 'AUTHORIZED';
    }

    public function capturePayment(Order $order, string $paymentId): array
    {
        $items = $this->prepareOrderItems($order);

        $payload = [
            'amount' => number_format($order->total, 2, '.', ''),
            'tax_amount' => number_format($order->tax, 2, '.', ''),
            'shipping_amount' => number_format($order->shipping, 2, '.', ''),
            'discount_amount' => number_format($order->discount, 2, '.', ''),
            'created_at' => now()->toISOString(),
            'items' => $items,
            'reference_id' => $order->order_number,
        ];

        $response = $this->makeRequest("/payments/{$paymentId}/captures", $payload);

        if (isset($response['status']) && $response['status'] === 'CLOSED') {
            return [
                'captured' => true,
                'capture_id' => $response['id'] ?? null,
            ];
        }

        throw new \Exception('Failed to capture Tabby payment');
    }

    public function refundPayment(Payment $payment, float $amount): array
    {
        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
        ];

        $response = $this->makeRequest("/payments/{$payment->payment_id}/refunds", $payload);

        return [
            'refund_id' => $response['id'] ?? null,
        ];
    }

    public function handleWebhook(array $payload): void
    {
        $this->logRequest('webhook', $payload, null);
    }

    public function getGatewayName(): string
    {
        return 'tabby';
    }

    /**
     * Prepare order items for Tabby
     */
    protected function prepareOrderItems(Order $order): array
    {
        return $order->items->map(function ($item) {
            return [
                'title' => $item->product->name['en'] ?? 'Product',
                'description' => $item->product->description['en'] ?? '',
                'quantity' => $item->quantity,
                'unit_price' => number_format($item->unit_price, 2, '.', ''),
                'discount_amount' => '0.00',
                'reference_id' => (string) $item->product_id,
                'image_url' => $item->product->images->first()->image_url ?? '',
                'category' => 'product',
            ];
        })->toArray();
    }

    /**
     * Format address for Tabby
     */
    protected function formatAddress(?array $address): array
    {
        return [
            'city' => $address['city'] ?? 'Riyadh',
            'address' => $address['address'] ?? 'N/A',
            'zip' => $address['zip'] ?? '12345',
        ];
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }
}

<?php

namespace App\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;

class TamaraGateway extends BasePaymentGateway
{
    protected $publicKey;
    protected $notificationToken;

    public function __construct()
    {
        $this->testMode = config('payment.gateways.tamara.test_mode');
        $this->apiKey = $this->testMode
            ? config('payment.gateways.tamara.test_api_token')
            : config('payment.gateways.tamara.live_api_token');
        $this->publicKey = $this->testMode
            ? config('payment.gateways.tamara.test_public_key')
            : config('payment.gateways.tamara.live_public_key');
        $this->notificationToken = $this->testMode
            ? config('payment.gateways.tamara.test_notification_token')
            : config('payment.gateways.tamara.live_notification_token');
        $this->apiURL = $this->testMode
            ? config('payment.gateways.tamara.test_url')
            : config('payment.gateways.tamara.live_url');
        $this->currency = config('payment.gateways.tamara.currency', 'SAR');
    }

    public function createCheckoutSession(Order $order): array
    {
        $user = $order->user;
        $items = $this->prepareOrderItems($order);

        $payload = [
            'order_reference_id' => $order->order_number,
            'order_number' => $order->order_number,
            'total_amount' => [
                'amount' => (float) $order->total,
                'currency' => $this->currency,
            ],
            'description' => 'Order #' . $order->order_number,
            'country_code' => $this->testMode ? 'AE' : 'SA',
            'payment_type' => 'PAY_BY_INSTALMENTS',
            'locale' => app()->getLocale() === 'ar' ? 'ar_SA' : 'en_US',
            'items' => $items,
            'consumer' => [
                'first_name' => $user->name ?? 'Customer',
                'last_name' => $user->name ?? 'User',
                'phone_number' => $user->user_phone ?? '500000001',
                'email' => $user->email ?? 'customer@example.com',
            ],
            'billing_address' => $this->formatAddress($order->billing_address),
            'shipping_address' => $this->formatAddress($order->shipping_address ?? $order->billing_address),
            'tax_amount' => [
                'amount' => (float) $order->tax,
                'currency' => $this->currency,
            ],
            'shipping_amount' => [
                'amount' => (float) $order->shipping,
                'currency' => $this->currency,
            ],
            'merchant_url' => [
                'success' => config('payment.gateways.tamara.callback_url') . '?gateway=tamara&status=success',
                'cancel' => config('payment.gateways.tamara.callback_url') . '?gateway=tamara&status=cancel',
                'failure' => config('payment.gateways.tamara.callback_url') . '?gateway=tamara&status=failure',
                'notification' => config('payment.gateways.tamara.webhook_url'),
            ],
        ];

        $response = $this->makeRequest('/checkout', $payload);

        return [
            'payment_url' => $response['checkout_url'] ?? null,
            'order_id' => $response['order_id'] ?? null,
        ];
    }

    public function verifyPayment(string $paymentId): bool
    {
        $response = $this->makeRequest("/orders/{$paymentId}", [], 'GET');

        return isset($response['status']) && $response['status'] === 'approved';
    }

    public function capturePayment(Order $order, string $paymentId): array
    {
        // First, authorize the order
        $authorizeResponse = $this->makeRequest("/orders/{$paymentId}/authorise", []);

        if (!isset($authorizeResponse['order_id'])) {
            throw new \Exception('Failed to authorize Tamara payment');
        }

        // Then capture the payment
        $items = $this->prepareOrderItems($order);

        $payload = [
            'order_id' => $paymentId,
            'total_amount' => [
                'amount' => (float) $order->total,
                'currency' => $this->currency,
            ],
            'tax_amount' => [
                'amount' => (float) $order->tax,
                'currency' => $this->currency,
            ],
            'shipping_amount' => [
                'amount' => (float) $order->shipping,
                'currency' => $this->currency,
            ],
            'discount_amount' => [
                'amount' => (float) $order->discount,
                'currency' => $this->currency,
            ],
            'items' => $items,
            'shipping_info' => [
                'shipped_at' => now()->toIso8601String(),
                'shipping_company' => config('app.name'),
            ],
        ];

        $response = $this->makeRequest('/payments/capture', $payload);

        return [
            'captured' => isset($response['capture_id']),
            'capture_id' => $response['capture_id'] ?? null,
        ];
    }

    public function refundPayment(Payment $payment, float $amount): array
    {
        $payload = [
            'order_id' => $payment->payment_id,
            'total_amount' => [
                'amount' => $amount,
                'currency' => $this->currency,
            ],
            'comment' => 'Refund for order #' . $payment->order->order_number,
        ];

        $response = $this->makeRequest('/payments/refund', $payload);

        return [
            'refund_id' => $response['refund_id'] ?? null,
        ];
    }

    public function handleWebhook(array $payload): void
    {
        $this->logRequest('webhook', $payload, null);
    }

    public function getGatewayName(): string
    {
        return 'tamara';
    }

    /**
     * Prepare order items for Tamara
     */
    protected function prepareOrderItems(Order $order): array
    {
        return $order->items->map(function ($item) {
            return [
                'reference_id' => (string) $item->product_id,
                'type' => 'Digital',
                'name' => $item->product->name['en'] ?? 'Product',
                'sku' => 'PROD-' . $item->product_id,
                'image_url' => $item->product->images->first()->image_url ?? '',
                'quantity' => $item->quantity,
                'unit_price' => [
                    'amount' => (float) $item->unit_price,
                    'currency' => $this->currency,
                ],
                'discount_amount' => [
                    'amount' => 0.00,
                    'currency' => $this->currency,
                ],
                'tax_amount' => [
                    'amount' => 0.00,
                    'currency' => $this->currency,
                ],
                'total_amount' => [
                    'amount' => (float) $item->total_price,
                    'currency' => $this->currency,
                ],
            ];
        })->toArray();
    }

    /**
     * Format address for Tamara
     */
    protected function formatAddress(?array $address): array
    {
        return [
            'first_name' => $address['name'] ?? 'Customer',
            'last_name' => $address['name'] ?? 'User',
            'line1' => $address['address'] ?? 'N/A',
            'line2' => '',
            'region' => $address['region'] ?? 'Region',
            'postal_code' => $address['zip'] ?? '12345',
            'city' => $address['city'] ?? 'Riyadh',
            'country_code' => $this->testMode ? 'AE' : 'SA',
            'phone_number' => $address['phone'] ?? '500000001',
        ];
    }

    /**
     * Check payment options availability
     */
    public function checkPaymentOptions(float $amount, string $phoneNumber): array
    {
        $payload = [
            'country' => $this->testMode ? 'AE' : 'SA',
            'order_value' => [
                'amount' => $amount,
                'currency' => $this->currency,
            ],
            'phone_number' => $phoneNumber,
            'is_vip' => false,
        ];

        $response = $this->makeRequest('/checkout/payment-options-pre-check', $payload);

        return $response['has_available_payment_options'] ?? false
            ? $response['available_payment_labels'] ?? []
            : [];
    }
}

<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Create checkout session and return payment URL
     */
    public function createCheckoutSession(Order $order): array;

    /**
     * Verify payment status
     */
    public function verifyPayment(string $paymentId): bool;

    /**
     * Capture authorized payment
     */
    public function capturePayment(Order $order, string $paymentId): array;

    /**
     * Refund payment
     */
    public function refundPayment(Payment $payment, float $amount): array;

    /**
     * Handle webhook callback
     */
    public function handleWebhook(array $payload): void;

    /**
     * Get gateway name
     */
    public function getGatewayName(): string;
}

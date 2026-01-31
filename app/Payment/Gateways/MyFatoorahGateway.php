<?php

namespace App\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;

class MyFatoorahGateway extends BasePaymentGateway
{
    public function __construct()
    {
        $this->testMode = config('payment.gateways.myfatoorah.test_mode');
        $this->apiKey = $this->testMode
            ? config('payment.gateways.myfatoorah.test_api_key')
            : config('payment.gateways.myfatoorah.live_api_key');
        $this->apiURL = $this->testMode
            ? config('payment.gateways.myfatoorah.test_url')
            : config('payment.gateways.myfatoorah.live_url');
        $this->currency = config('payment.gateways.myfatoorah.currency', 'KWD');
    }

    public function createCheckoutSession(Order $order): array
    {
        $payload = [
            'PaymentMethodId'       => 2,
            'CustomerName'          => $order->user->name,
            'InvoiceValue'          => (float) $order->total,
            'DisplayCurrencyIso'    => $this->currency,
            'CustomerEmail'         => $order->user->email,
            'CallBackUrl'           => config('payment.gateways.myfatoorah.callback_url'),
            'ErrorUrl'              => config('payment.gateways.myfatoorah.error_url'),
            'Language'              => app()->getLocale() === 'ar' ? 'ar' : 'en',
            'CustomerReference'     => $order->order_number,
            'UserDefinedField'      => $order->id,
        ];

        $response = $this->makeRequest('v2/ExecutePayment', $payload);

        $this->handleError($response);

        return [
            'payment_url' => $response['Data']['PaymentURL'] ?? null,
            'payment_id'  => $response['Data']['InvoiceId'] ?? null, // MyFatoorah InvoiceId
        ];
    }

    public function verifyPayment(string $paymentId): bool
    {
        $payload = [
            'Key' => $paymentId,
            'KeyType' => 'PaymentId',
        ];

        $response = $this->makeRequest('v2/GetPaymentStatus', $payload);

        $this->handleError($response);

        return isset($response['Data']['InvoiceStatus'])
            && $response['Data']['InvoiceStatus'] === 'Paid';
    }

    public function capturePayment(Order $order, string $paymentId): array
    {
        // MyFatoorah doesn't require explicit capture
        // Payment is auto-captured upon successful payment
        return $this->getPaymentStatus($paymentId);
    }

    public function refundPayment(Payment $payment, float $amount): array
    {
        $payload = [
            'KeyType' => 'PaymentId',
            'Key' => $payment->payment_id,
            'RefundChargeOnCustomer' => false,
            'ServiceChargeOnCustomer' => false,
            'Amount' => $amount,
            'Comment' => 'Refund for order #' . $payment->order->order_number,
        ];

        $response = $this->makeRequest('v2/MakeRefund', $payload);

        $this->handleError($response);

        return [
            'refund_id' => $response['Data']['RefundId'] ?? null,
            'refund_reference' => $response['Data']['RefundReference'] ?? null,
        ];
    }

    public function handleWebhook(array $payload): void
    {
        // MyFatoorah webhook handling
        // Usually verify signature and process payment status
        $this->logRequest('webhook', $payload, null);
    }

    public function getGatewayName(): string
    {
        return 'myfatoorah';
    }

    /**
     * Get payment status details
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $payload = [
            'Key' => $paymentId,
            'KeyType' => 'PaymentId',
        ];

        $response = $this->makeRequest('v2/GetPaymentStatus', $payload);

        $this->handleError($response);

        return $response['Data'] ?? [];
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethods(float $amount): array
    {
        $payload = [
            'InvoiceAmount' => $amount,
            'CurrencyIso' => $this->currency,
        ];

        $response = $this->makeRequest('v2/InitiatePayment', $payload);

        $this->handleError($response);

        return $response['Data']['PaymentMethods'] ?? [];
    }
}

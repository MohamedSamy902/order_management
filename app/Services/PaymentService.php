<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Payment\PaymentGatewayFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Initiate payment for an order
     */
    public function initiatePayment(Order $order): array
    {
        try {
            $gateway = PaymentGatewayFactory::make($order->payment_method);

            $result = $gateway->createCheckoutSession($order);

            // Create payment record
            Payment::create([
                'order_id'      => $order->id,
                'payment_id'    => $result['payment_id'] ?? $result['invoice_id'] ?? $result['order_id'] ?? uniqid('payment_'),
                'gateway'       => $order->payment_method,
                'amount'        => $order->total,
                'currency'      => config("payment.gateways.{$order->payment_method}.currency", 'SAR'),
                'status'        => 'pending',
            ]);

            Log::info("Payment initiated", [
                'order_id'      => $order->id,
                'gateway'       => $order->payment_method,
                'payment_url'   => $result['payment_url'] ?? null,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error("Payment initiation failed", [
                'order_id'      => $order->id,
                'error'         => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle payment callback from gateway
     */
    public function handlePaymentCallback(string $paymentId, string $gatewayName): Payment
    {
        return DB::transaction(function () use ($paymentId, $gatewayName) {
            $payment = Payment::where('payment_id', $paymentId)->firstOrFail();
            $gateway = PaymentGatewayFactory::make($gatewayName);

            // Verify payment
            $isVerified = $gateway->verifyPayment($paymentId);

            if ($isVerified) {
                // Update payment status
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Update order payment status
                $this->orderService->updatePaymentStatus($payment->order, 'paid');
                $this->orderService->updateOrderStatus($payment->order, 'processing');

                Log::info("Payment verified successfully", [
                    'payment_id' => $paymentId,
                    'order_id' => $payment->order_id,
                ]);
            } else {
                $payment->update(['status' => 'failed']);
                $this->orderService->updatePaymentStatus($payment->order, 'failed');

                Log::warning("Payment verification failed", [
                    'payment_id' => $paymentId,
                    'order_id' => $payment->order_id,
                ]);
            }

            return $payment->fresh(['order']);
        });
    }

    /**
     * Capture/Complete payment (for backwards compatibility)
     * Since we now use 'paid' status directly, this just marks order as completed
     */
    public function capturePayment(Payment $payment): Payment
    {
        if (!$payment->isPaid()) {
            throw new \Exception('Payment must be paid before marking as completed');
        }

        return DB::transaction(function () use ($payment) {
            // Mark order as completed
            $this->orderService->updateOrderStatus($payment->order, 'completed');

            Log::info("Order marked as completed", [
                'payment_id' => $payment->payment_id,
                'order_id' => $payment->order_id,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Refund payment
     */
    public function refundPayment(Payment $payment, ?float $amount = null): Payment
    {
        if (!$payment->canBeRefunded()) {
            throw new \Exception('Payment cannot be refunded');
        }

        $refundAmount = $amount ?? $payment->amount;

        if ($refundAmount > $payment->amount) {
            throw new \Exception('Refund amount exceeds payment amount');
        }

        return DB::transaction(function () use ($payment, $refundAmount) {
            $gateway = PaymentGatewayFactory::make($payment->gateway);

            $result = $gateway->refundPayment($payment, $refundAmount);

            $payment->update([
                'status' => 'refunded',
                'gateway_response' => array_merge($payment->gateway_response ?? [], [
                    'refund' => $result,
                ]),
            ]);

            $this->orderService->updatePaymentStatus($payment->order, 'refunded');

            // Restore stock
            foreach ($payment->order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            Log::info("Payment refunded", [
                'payment_id' => $payment->payment_id,
                'order_id' => $payment->order_id,
                'amount' => $refundAmount,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Handle webhook from gateway
     */
    public function handleWebhook(string $gatewayName, array $payload): void
    {
        $gateway = PaymentGatewayFactory::make($gatewayName);

        $gateway->handleWebhook($payload);

        Log::info("Webhook received", [
            'gateway' => $gatewayName,
            'payload' => $payload,
        ]);
    }

    /**
     * Handle MyFatoorah callback (special case)
     * MyFatoorah sends PaymentId but we save InvoiceId, so we fetch order via CustomerReference
     */
    public function handleMyFatoorahCallback(string $paymentId): Payment
    {
        return DB::transaction(function () use ($paymentId) {
            $gateway = PaymentGatewayFactory::make('myfatoorah');

            // Ensure we have MyFatoorahGateway instance
            if (!($gateway instanceof \App\Payment\Gateways\MyFatoorahGateway)) {
                throw new \Exception('Invalid gateway instance');
            }

            // Get payment details from MyFatoorah API
            $paymentDetails = $gateway->getPaymentStatus($paymentId);

            // Extract order_number from CustomerReference
            $orderNumber = $paymentDetails['CustomerReference'] ?? null;

            if (!$orderNumber) {
                throw new \Exception('Order reference not found in payment response');
            }

            // Find order by order_number
            $order = Order::where('order_number', $orderNumber)->firstOrFail();

            // Find payment record
            $payment = Payment::where('order_id', $order->id)
                ->where('gateway', 'myfatoorah')
                ->latest()
                ->firstOrFail();

            // Verify payment status
            $isPaid = isset($paymentDetails['InvoiceStatus'])
                && $paymentDetails['InvoiceStatus'] === 'Paid';

            if ($isPaid) {
                // Update payment
                $payment->update([
                    'status' => 'paid', // MyFatoorah auto-captures
                    'transaction_id' => $paymentId, // Save PaymentId as transaction_id
                    'paid_at' => now(),
                    'gateway_response' => $paymentDetails,
                ]);

                // Update order
                $this->orderService->updatePaymentStatus($order, 'paid');
                $this->orderService->updateOrderStatus($order, 'processing');

                Log::info("MyFatoorah payment verified", [
                    'payment_id' => $paymentId,
                    'order_number' => $orderNumber,
                    'order_id' => $order->id,
                ]);
            } else {
                $payment->update(['status' => 'failed']);
                $this->orderService->updatePaymentStatus($order, 'failed');

                Log::warning("MyFatoorah payment failed", [
                    'payment_id' => $paymentId,
                    'order_number' => $orderNumber,
                ]);
            }

            return $payment->fresh(['order']);
        });
    }
}

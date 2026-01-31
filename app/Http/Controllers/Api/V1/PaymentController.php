<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\User;
use App\Models\Order;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Transformers\API\V1\PaymentTransformer;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected PaymentTransformer $transformer;

    public function __construct(PaymentService $paymentService, PaymentTransformer $transformer)
    {
        $this->paymentService = $paymentService;
        $this->transformer = $transformer;
    }

    public function initiate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'order_id' => ['required', 'exists:orders,id'],
            ]);

            $user = auth()->user();
            $order = Order::where('id', $request->order_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            if ($order->payment_status !== 'pending') {
                return ApiResponse::error(__('Order payment already processed'), null, 400);
            }

            $result = $this->paymentService->initiatePayment($order);

            return ApiResponse::success([
                'payment_url' => $result['payment_url'],
                'order' => $order->order_number,
            ], __('Payment initiated successfully'));
        } catch (Exception $e) {
            return ApiResponse::error(
                __('Failed to initiate payment: ') . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function callback(Request $request, string $gateway): JsonResponse
    {
        try {
            // MyFatoorah sends PaymentId but we save InvoiceId, so we need special handling
            if ($gateway === 'myfatoorah') {
                return $this->handleMyFatoorahCallback($request);
            }

            $paymentId = $request->input('paymentId')
                ?? $request->input('Id')
                ?? $request->input('payment_id')
                ?? $request->input('order_id');

            if (!$paymentId) {
                return ApiResponse::error(__('Payment ID not provided'), null, 400);
            }

            $payment = $this->paymentService->handlePaymentCallback($paymentId, $gateway);

            if ($payment->status === 'paid') {
                return ApiResponse::success(
                    $this->transformer->transform($payment),
                    __('Payment successful')
                );
            }

            return ApiResponse::error(
                __('Payment failed or pending'),
                ['payment_status' => $payment->status],
                400
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                __('Payment callback failed: ') . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Handle MyFatoorah callback (special case)
     */
    protected function handleMyFatoorahCallback(Request $request): JsonResponse
    {
        try {
            $paymentId = $request->input('paymentId') ?? $request->input('Id');

            if (!$paymentId) {
                return ApiResponse::error(__('Payment ID not provided'), null, 400);
            }

            // Get payment details from MyFatoorah API to find the order_number
            $payment = $this->paymentService->handleMyFatoorahCallback($paymentId);

            if ($payment->status === 'paid') {
                return ApiResponse::success(
                    $this->transformer->transform($payment),
                    __('Payment successful')
                );
            }

            return ApiResponse::error(
                __('Payment failed'),
                ['payment_status' => $payment->status],
                400
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                __('Payment callback failed: ') . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function webhook(Request $request, string $gateway)
    {
        try {
            $payload = $request->all();

            $this->paymentService->handleWebhook($gateway, $payload);

            return response()->json(['status' => 'success'], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

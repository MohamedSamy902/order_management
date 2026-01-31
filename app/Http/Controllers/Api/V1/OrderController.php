<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\User;
use App\Helpers\ApiResponse;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Order\OrderRequest;
use App\Http\Requests\Api\Order\UpdateOrderRequest;
use App\Transformers\API\V1\OrderTransformer;

class OrderController extends Controller
{
    protected OrderService $orderService;
    protected OrderTransformer $transformer;

    public function __construct(OrderService $orderService, OrderTransformer $transformer)
    {
        $this->orderService = $orderService;
        $this->transformer = $transformer;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // Get filters from request
            $filters = $request->only(['status', 'payment_status']);
            $perPage = $request->get('per_page', 15);

            $orders = $this->orderService->getUserOrders($user, $filters, $perPage);

            return ApiResponse::success(
                $this->transformer->transformPaginate($orders),
                __('Orders retrieved successfully')
            );
        } catch (Exception $e) {
            return ApiResponse::error(__('Failed to retrieve orders'), null, 500);
        }
    }

    public function store(OrderRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $data = $request->validated();

            // Validate stock before creating order
            if (!$this->orderService->validateStock($data['items'])) {
                return ApiResponse::error(__('Insufficient stock for one or more products'), null, 400);
            }

            $order = $this->orderService->createOrder($user, $data['items'], $data);

            return ApiResponse::created(
                $this->transformer->transform($order),
                __('Order created successfully')
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                __('Failed to create order: ') . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $order = $this->orderService->getOrderById($id, $user);

            return ApiResponse::success(
                $this->transformer->transform($order),
                __('Order retrieved successfully')
            );
        } catch (Exception $e) {
            return ApiResponse::error(__('Order not found'), null, 404);
        }
    }

    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $order = $this->orderService->getOrderById($id, $user);

            $updatedOrder = $this->orderService->updateOrder($order, $request->validated());

            return ApiResponse::success(
                $this->transformer->transform($updatedOrder),
                __('Order updated successfully')
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                __('Failed to update order: ') . $e->getMessage(),
                null,
                400
            );
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $order = $this->orderService->getOrderById($id, $user);

            $this->orderService->deleteOrder($order);

            return ApiResponse::success(
                null,
                __('Order deleted successfully')
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                __('Failed to delete order: ') . $e->getMessage(),
                null,
                400
            );
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $order = $this->orderService->getOrderById($id, $user);

            $cancelledOrder = $this->orderService->cancelOrder($order);

            return ApiResponse::success(
                $this->transformer->transform($cancelledOrder),
                __('Order cancelled successfully')
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                __('Failed to cancel order: ') . $e->getMessage(),
                null,
                400
            );
        }
    }
}

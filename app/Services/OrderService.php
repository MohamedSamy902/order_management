<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Create new order with items
     */
    public function createOrder(User $user, array $items, array $data): Order
    {
        return DB::transaction(function () use ($user, $items, $data) {
            // Calculate pricing
            $pricing = $this->calculatePricing($items);

            // Calculate tax (15% VAT)
            $tax = $pricing['subtotal'] * 0.15;

            // Calculate shipping based on subtotal
            $shipping = $this->calculateShipping($pricing['subtotal']);

            // Calculate discount
            $discount = $data['discount'] ?? 0;

            // Calculate final total
            $total = $pricing['subtotal'] + $tax + $shipping - $discount;

            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'subtotal' => $pricing['subtotal'],
                'tax' => $tax,
                'shipping' => $shipping,
                'discount' => $discount,
                'total' => $total,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? $data['billing_address'] ?? null,
            ]);

            // Create order items and update stock
            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check stock availability
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                // Create order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'total_price' => $product->price * $item['quantity'],
                ]);

                // Reduce stock
                $product->decrement('stock', $item['quantity']);
            }

            return $order->load(['items.product', 'user']);
        });
    }

    /**
     * Get user orders with filtering and pagination
     */
    public function getUserOrders(User $user, array $filters = [], int $perPage = 15)
    {
        $query = Order::with(['items.product.images', 'payments'])
            ->where('user_id', $user->id);

        // Filter by order status
        if (isset($filters['status']) && $filters['status']) {
            $query->where('order_status', $filters['status']);
        }

        // Filter by payment status
        if (isset($filters['payment_status']) && $filters['payment_status']) {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get order by ID
     */
    public function getOrderById(int $id, ?User $user = null): Order
    {
        $query = Order::with(['items.product.images', 'payments', 'user']);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query->findOrFail($id);
    }

    /**
     * Update order details
     * Only allows updating notes and addresses
     */
    public function updateOrder(Order $order, array $data): Order
    {
        // Cannot update if payment is processed
        if ($order->payment_status === 'paid') {
            throw new \Exception('Cannot update order after payment is processed');
        }

        $order->update([
            'notes' => $data['notes'] ?? $order->notes,
            'billing_address' => $data['billing_address'] ?? $order->billing_address,
            'shipping_address' => $data['shipping_address'] ?? $order->shipping_address,
        ]);

        Log::info("Order updated", [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        return $order->fresh(['items.product.images', 'payments', 'user']);
    }

    /**
     * Delete order (only if no payments exist)
     */
    public function deleteOrder(Order $order): bool
    {
        // Business Rule: Cannot delete if payments exist
        if ($order->payments()->exists()) {
            throw new \Exception('Cannot delete order with associated payments');
        }

        // Cannot delete if payment is processed
        if ($order->payment_status === 'paid') {
            throw new \Exception('Cannot delete paid order');
        }

        return DB::transaction(function () use ($order) {
            // Restore stock for all items
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            // Delete order items first
            $order->items()->delete();

            // Delete the order
            $deleted = $order->delete();

            Log::info("Order deleted", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return $deleted;
        });
    }

    /**
     * Calculate order pricing
     */
    protected function calculatePricing(array $items): array
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $subtotal += $product->price * $item['quantity'];
        }

        return [
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ];
    }

    /**
     * Calculate shipping cost based on subtotal
     */
    protected function calculateShipping(float $subtotal): float
    {
        // Free shipping for orders over 500 SAR
        if ($subtotal >= 500) {
            return 0;
        }

        // 50 SAR for orders over 200 SAR
        if ($subtotal >= 200) {
            return 50;
        }

        // 100 SAR for orders under 200 SAR
        return 100;
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Order $order, string $status): Order
    {
        $order->update(['order_status' => $status]);

        Log::info("Order status updated", [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $status,
        ]);

        return $order->fresh();
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Order $order, string $status): Order
    {
        $order->update(['payment_status' => $status]);

        Log::info("Payment status updated", [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $status,
        ]);

        return $order->fresh();
    }

    /**
     * Cancel order and restore stock
     */
    public function cancelOrder(Order $order): Order
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception('Order cannot be cancelled');
        }

        return DB::transaction(function () use ($order) {
            // Restore stock
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            // Update status
            $order->update([
                'order_status' => 'cancelled',
                'payment_status' => 'failed',
            ]);

            Log::info("Order cancelled", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return $order->fresh();
        });
    }

    /**
     * Validate stock availability
     */
    public function validateStock(array $items): bool
    {
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);

            if (!$product || $product->stock < $item['quantity']) {
                return false;
            }
        }

        return true;
    }
}

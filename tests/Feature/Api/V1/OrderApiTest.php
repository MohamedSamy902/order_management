<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class OrderApiTest extends TestCase
{
    use DatabaseMigrations;

    public function test_user_can_create_order()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 100]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/orders', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2]
                ],
                'payment_method' => 'myfatoorah',
                'billing_address' => [
                    'name' => 'Test User',
                    'phone' => '1234567890',
                    'city' => 'Test City',
                    'address' => 'Test Address',
                ],
                'shipping_address' => [
                    'name' => 'Test User',
                    'phone' => '1234567890',
                    'city' => 'Test City',
                    'address' => 'Test Address',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['order_number', 'total'],
                'message'
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_status' => 'pending',
        ]);
    }

    public function test_user_can_view_their_orders()
    {
        $user = User::factory()->create();
        Order::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items' => [
                        '*' => ['order_number', 'total', 'order_status']
                    ]
                ],
            ]);
    }

    public function test_user_can_update_order()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending'
        ]);

        $response = $this->actingAs($user, 'api')
            ->putJson("/api/v1/orders/{$order->id}", [
                'notes' => 'Updated notes for my order',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'notes' => 'Updated notes for my order',
        ]);
    }

    public function test_cannot_update_paid_order()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'paid'
        ]);

        $response = $this->actingAs($user, 'api')
            ->putJson("/api/v1/orders/{$order->id}", [
                'notes' => 'Trying to update',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_user_can_delete_pending_order()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending'
        ]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('orders', [
            'id' => $order->id,
        ]);
    }

    public function test_cannot_delete_order_with_payments()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Create a payment for this order
        $order->payments()->create([
            'payment_id' => 'test123',
            'gateway' => 'myfatoorah',
            'amount' => 100,
            'currency' => 'SAR',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(400)
            ->assertJsonFragment([
                'success' => false,
            ]);

        // Order should still exist
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
        ]);
    }

    public function test_cannot_delete_paid_order()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'paid'
        ]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(400);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
        ]);
    }

    public function test_can_filter_orders_by_status()
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'order_status' => 'pending']);
        Order::factory()->create(['user_id' => $user->id, 'order_status' => 'processing']);
        Order::factory()->create(['user_id' => $user->id, 'order_status' => 'completed']);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/orders?status=pending');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_can_filter_orders_by_payment_status()
    {
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'payment_status' => 'pending']);
        Order::factory()->create(['user_id' => $user->id, 'payment_status' => 'paid']);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/orders?payment_status=paid');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }
}

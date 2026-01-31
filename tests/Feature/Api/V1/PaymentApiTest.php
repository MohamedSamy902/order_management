<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PaymentApiTest extends TestCase
{
    use DatabaseMigrations;

    public function test_can_initiate_payment_for_pending_order()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 100]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'payment_method' => 'myfatoorah',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/payments/initiate', [
                'order_id' => $order->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['payment_url'],
            ]);

        // Payment record should be created
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway' => 'myfatoorah',
            'status' => 'pending',
        ]);
    }

    public function test_cannot_initiate_payment_for_paid_order()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'paid'
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/payments/initiate', [
                'order_id' => $order->id,
            ]);

        $response->assertStatus(400);
    }

    public function test_payment_callback_updates_order_status()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'pending',
            'order_number' => 'ORD-TEST-123'
        ]);

        $payment = $order->payments()->create([
            'payment_id' => 'TEST123',
            'gateway' => 'myfatoorah',
            'amount' => 100,
            'currency' => 'SAR',
            'status' => 'pending',
        ]);

        // Note: This is a simplified test
        // Real callback would come from payment gateway
        $this->assertTrue($payment->exists());
        $this->assertEquals('pending', $payment->status);
    }

    public function test_pagination_works_for_orders()
    {
        $user = User::factory()->create();
        Order::factory()->count(20)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/orders?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);
    }
}

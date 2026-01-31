<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 50, 500);
        $tax = $subtotal * 0.15;
        $shipping = 50.00;
        $discount = 0.00;
        $total = $subtotal + $tax + $shipping - $discount;

        return [
            'order_number' => Order::generateOrderNumber(),
            'user_id' => User::factory(),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => $total,
            'payment_method' => 'credit_card',
            'payment_status' => 'unpaid',
            'order_status' => 'pending',
            'notes' => $this->faker->sentence,
            'billing_address' => [
                'name' => $this->faker->name,
                'phone' => $this->faker->phoneNumber,
                'city' => $this->faker->city,
                'address' => $this->faker->address,
            ],
            'shipping_address' => [
                'name' => $this->faker->name,
                'phone' => $this->faker->phoneNumber,
                'city' => $this->faker->city,
                'address' => $this->faker->address,
            ],
        ];
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(fn(array $attributes) => [
            'payment_status' => 'paid',
            'order_status' => 'processing',
        ]);
    }

    /**
     * Indicate that the order is complete.
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ]);
    }
}

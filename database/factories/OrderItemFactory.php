<?php

namespace Database\Factories;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Order::factory(),
            'product_id'=>\App\Models\Product::factory(),
            'quantity'=> fake()->numberBetween(1,10),
            'unit_price'=>fake()->randomFloat(2, 10, 5000),
        ];
    }
}

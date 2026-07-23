<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::factory(),
            'order_number' =>fake() ->unique() -> numerify('ORD-######'),
            'total_amount' => fake() -> randomfloat(2,50,10000),
            'status' => fake()->randomElement(['pending','confirmed','shipped' , 'completed' , 'cancelled' ]),
        ];
    }
}

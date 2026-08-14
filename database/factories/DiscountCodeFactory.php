<?php

namespace Database\Factories;

use App\Models\DiscountCode;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountCode>
 */
class DiscountCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('????##')),
            'discount_type' => 'percentage',
            'discount_value' => fake()->randomFloat(2, 5, 50),
            'min_order_amount' => null,
            'product_id' => Product::factory(),
            'category_id' => null,
        ];
    }
}

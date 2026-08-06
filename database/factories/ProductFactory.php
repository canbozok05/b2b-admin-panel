<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $words = fake()->words(3);
        $name = ucfirst(implode(' ', is_array($words) ? $words : [$words]));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'sku' => strtoupper(fake()->bothify('???-###')),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 5000),
            'stock_quantity' => fake()->numberBetween(0, 200),
            'is_published' => fake()->boolean(80),
        ];
    }
}

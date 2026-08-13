<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $words = fake()->words(2);
        $name = ucfirst(implode(' ', is_array($words) ? $words : [$words]));

        return [
            'name' => $name.' Kampanyası',
            'discount_type' => 'percentage',
            'discount_value' => fake()->randomFloat(2, 5, 50),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
            'product_id' => Product::factory(),
            'category_id' => null,
        ];
    }
}

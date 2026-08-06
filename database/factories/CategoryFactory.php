<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => fake()->boolean(90),
            'parent_id' => null,

        ];
    }
}

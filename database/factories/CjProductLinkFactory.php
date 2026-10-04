<?php

namespace Database\Factories;

use App\Models\CjProductLink;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CjProductLink>
 */
class CjProductLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'cj_product_id' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'cj_variant_id' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'cj_cost_price' => fake()->randomFloat(2, 2, 100),
            'cj_warehouse_country' => fake()->randomElement(['US', 'CN', 'GB']),
            'cj_last_synced_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'description' => fake()->paragraph(),
            'logo_path' => null,
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'whatsapp' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'website' => fake()->optional()->url(),
            'status' => Vendor::STATUS_APPROVED,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Vendor::STATUS_PENDING,
        ]);
    }

    public function cjEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'cj_dropshipping_enabled' => true,
            'cj_markup_percent' => 35.00,
        ]);
    }
}

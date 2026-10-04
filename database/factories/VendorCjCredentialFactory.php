<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorCjCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorCjCredential>
 */
class VendorCjCredentialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'access_token' => fake()->uuid(),
            'refresh_token' => fake()->uuid(),
            'access_token_expires_at' => now()->addDays(180),
            'refresh_token_expires_at' => now()->addDays(180),
            'connected_at' => now(),
        ];
    }
}

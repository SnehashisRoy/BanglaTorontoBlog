<?php

namespace Tests\Feature\Vendor;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCjCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CjSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_vendor_without_cj_enabled_cannot_view_the_settings_page(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();

        $response = $this->actingAs($user)->get(route('vendor.cj.edit'));

        $response->assertForbidden();
    }

    public function test_a_cj_enabled_vendor_can_view_the_settings_page(): void
    {
        $user = User::factory()->has(Vendor::factory()->cjEnabled())->create();

        $response = $this->actingAs($user)->get(route('vendor.cj.edit'));

        $response->assertOk();
    }

    public function test_a_vendor_can_connect_their_cj_account(): void
    {
        $user = User::factory()->has(Vendor::factory()->cjEnabled())->create();

        Http::fake([
            '*/authentication/getAccessToken' => Http::response([
                'result' => true,
                'data' => [
                    'accessToken' => 'access-123',
                    'accessTokenExpiryDate' => now()->addDays(180)->toIso8601String(),
                    'refreshToken' => 'refresh-123',
                    'refreshTokenExpiryDate' => now()->addDays(180)->toIso8601String(),
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->post(route('vendor.cj.connect'), ['api_key' => 'my-cj-key']);

        $response->assertRedirect(route('vendor.cj.edit'));
        $this->assertDatabaseHas('vendor_cj_credentials', ['vendor_id' => $user->vendor->id]);
    }

    public function test_a_failed_connection_shows_a_friendly_error_and_stores_nothing(): void
    {
        $user = User::factory()->has(Vendor::factory()->cjEnabled())->create();

        Http::fake([
            '*/authentication/getAccessToken' => Http::response([
                'result' => false,
                'message' => 'Invalid apiKey',
            ], 200),
        ]);

        $response = $this->actingAs($user)->post(route('vendor.cj.connect'), ['api_key' => 'bad-key']);

        $response->assertSessionHasErrors('api_key');
        $this->assertDatabaseCount('vendor_cj_credentials', 0);
    }

    public function test_a_vendor_can_disconnect_cj(): void
    {
        $vendor = Vendor::factory()->cjEnabled()->create();
        VendorCjCredential::factory()->for($vendor)->create();
        $user = $vendor->user;

        $response = $this->actingAs($user)->delete(route('vendor.cj.disconnect'));

        $response->assertRedirect(route('vendor.cj.edit'));
        $this->assertDatabaseCount('vendor_cj_credentials', 0);
    }

    public function test_updating_markup_immediately_reprices_linked_products(): void
    {
        $vendor = Vendor::factory()->cjEnabled()->create(['cj_markup_percent' => 20]);
        $product = Product::factory()->for($vendor)->create(['price' => 12.00]);
        $product->cjLink()->create([
            'cj_product_id' => '1001',
            'cj_cost_price' => 10.00,
            'cj_last_synced_at' => now(),
        ]);
        $user = $vendor->user;

        $response = $this->actingAs($user)->put(route('vendor.cj.markup'), ['cj_markup_percent' => 50]);

        $response->assertRedirect(route('vendor.cj.edit'));
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'cj_markup_percent' => 50]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'price' => 15.00]);
    }

    public function test_updating_markup_does_not_touch_manually_created_products(): void
    {
        $vendor = Vendor::factory()->cjEnabled()->create();
        $manualProduct = Product::factory()->for($vendor)->create(['price' => 99.99]);
        $user = $vendor->user;

        $this->actingAs($user)->put(route('vendor.cj.markup'), ['cj_markup_percent' => 50]);

        $this->assertDatabaseHas('products', ['id' => $manualProduct->id, 'price' => 99.99]);
    }
}

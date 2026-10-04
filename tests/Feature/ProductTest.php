<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_product_page_renders_with_vendor_contact_info(): void
    {
        $vendor = Vendor::factory()->create(['phone' => '416-555-0100', 'address' => '123 Danforth Ave']);
        $product = Product::factory()->for($vendor)->create(['status' => 'published']);

        $response = $this->get(route('shops.products.show', ['vendor' => $vendor->slug, 'product' => $product->slug]));

        $response->assertOk();
        $response->assertSee($product->name);
        $response->assertSee('416-555-0100');
        $response->assertSee('123 Danforth Ave');
    }

    public function test_draft_product_page_404s(): void
    {
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->for($vendor)->create(['status' => 'draft']);

        $response = $this->get(route('shops.products.show', ['vendor' => $vendor->slug, 'product' => $product->slug]));

        $response->assertNotFound();
    }

    public function test_product_from_wrong_vendor_slug_404s(): void
    {
        $vendor = Vendor::factory()->create();
        $otherVendor = Vendor::factory()->create();
        $product = Product::factory()->for($vendor)->create(['status' => 'published']);

        $response = $this->get(route('shops.products.show', ['vendor' => $otherVendor->slug, 'product' => $product->slug]));

        $response->assertNotFound();
    }

    public function test_a_cj_sourced_product_shows_a_shipping_disclosure(): void
    {
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->for($vendor)->create(['status' => 'published']);
        $product->cjLink()->create([
            'cj_product_id' => '1001',
            'cj_warehouse_country' => 'CN',
            'cj_cost_price' => 10,
            'cj_last_synced_at' => now(),
        ]);

        $response = $this->get(route('shops.products.show', ['vendor' => $vendor->slug, 'product' => $product->slug]));

        $response->assertOk();
        $response->assertSee('Ships from overseas');
        $response->assertSee('2–4 weeks');
    }

    public function test_a_regular_product_shows_no_shipping_disclosure(): void
    {
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->for($vendor)->create(['status' => 'published']);

        $response = $this->get(route('shops.products.show', ['vendor' => $vendor->slug, 'product' => $product->slug]));

        $response->assertOk();
        $response->assertDontSee('Ships from overseas');
    }
}

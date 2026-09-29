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
}

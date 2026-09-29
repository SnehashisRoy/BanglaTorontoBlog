<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_shops_index_lists_active_vendors(): void
    {
        $vendor = Vendor::factory()->create();
        $inactive = Vendor::factory()->inactive()->create();

        $response = $this->get(route('shops.index'));

        $response->assertOk();
        $response->assertSee($vendor->name);
        $response->assertDontSee($inactive->name);
    }

    public function test_shop_page_shows_only_its_own_published_products(): void
    {
        $vendor = Vendor::factory()->create();
        $otherVendor = Vendor::factory()->create();

        $ownProduct = Product::factory()->for($vendor)->create(['status' => 'published']);
        $draft = Product::factory()->for($vendor)->create(['status' => 'draft']);
        $otherProduct = Product::factory()->for($otherVendor)->create(['status' => 'published']);

        $response = $this->get(route('shops.show', $vendor->slug));

        $response->assertOk();
        $response->assertSee($ownProduct->name);
        $response->assertDontSee($draft->name);
        $response->assertDontSee($otherProduct->name);
    }

    public function test_inactive_vendor_shop_page_404s(): void
    {
        $vendor = Vendor::factory()->inactive()->create();

        $response = $this->get(route('shops.show', $vendor->slug));

        $response->assertNotFound();
    }
}

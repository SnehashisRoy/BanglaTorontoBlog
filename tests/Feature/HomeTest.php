<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
    }

    public function test_home_page_only_shows_published_products_from_active_vendors(): void
    {
        $activeVendor = Vendor::factory()->create();
        $inactiveVendor = Vendor::factory()->inactive()->create();

        $published = Product::factory()->for($activeVendor)->create(['status' => 'published']);
        $draft = Product::factory()->for($activeVendor)->create(['status' => 'draft']);
        $fromInactiveVendor = Product::factory()->for($inactiveVendor)->create(['status' => 'published']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee($published->name);
        $response->assertDontSee($draft->name);
        $response->assertDontSee($fromInactiveVendor->name);
    }

    public function test_home_page_can_filter_by_category(): void
    {
        $vendor = Vendor::factory()->create();
        $matching = ProductCategory::factory()->create();
        $other = ProductCategory::factory()->create();

        $wanted = Product::factory()->for($vendor)->for($matching, 'category')->create(['status' => 'published']);
        $unwanted = Product::factory()->for($vendor)->for($other, 'category')->create(['status' => 'published']);

        $response = $this->get(route('home', ['category' => $matching->slug]));

        $response->assertOk();
        $response->assertSee($wanted->name);
        $response->assertDontSee($unwanted->name);
    }
}

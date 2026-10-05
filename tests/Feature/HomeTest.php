<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
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

    public function test_logged_in_user_sees_a_logout_control_on_the_public_layout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('logout'), false);
    }

    public function test_sell_with_us_is_no_longer_a_nav_link(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();

        $content = $response->getContent();
        $headerEnd = strpos($content, '</header>');
        $header = substr($content, 0, $headerEnd !== false ? $headerEnd : 0);

        $this->assertStringNotContainsString('Sell With Us', $header);
        $this->assertStringContainsString('Sell With Us', $content);
    }

    public function test_hero_has_a_sell_with_us_button_that_links_to_registration_for_a_guest(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Sell With Us');
        $response->assertSee(route('vendor.intent', 'register'), false);
    }

    public function test_hero_sell_with_us_button_links_to_vendor_registration_for_a_logged_in_non_vendor(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('vendor.register'), false);
    }

    public function test_hero_sell_with_us_button_links_to_the_dashboard_for_an_existing_vendor(): void
    {
        $vendor = Vendor::factory()->create();

        $response = $this->actingAs($vendor->user)->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('vendor.dashboard'), false);
    }

    public function test_home_page_shows_featured_products(): void
    {
        $vendor = Vendor::factory()->create();
        $featured = Product::factory()->for($vendor)->featured()->create(['status' => 'published']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee($featured->name);
        $response->assertSee('Featured Products');
    }

    public function test_home_page_only_shows_featured_products_by_default(): void
    {
        $vendor = Vendor::factory()->create();
        $featured = Product::factory()->for($vendor)->featured()->create(['name' => 'Featured Saree', 'status' => 'published']);
        $notFeatured = Product::factory()->for($vendor)->create(['name' => 'Plain Kurti', 'status' => 'published']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee($featured->name);
        $response->assertDontSee($notFeatured->name);
    }

    public function test_home_page_shows_an_empty_state_when_nothing_is_featured(): void
    {
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->for($vendor)->create(['status' => 'published']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Featured Products');
        $response->assertDontSee($product->name);
        $response->assertSee('No featured products yet');
    }

    public function test_home_page_hides_featured_products_that_are_draft_or_from_inactive_vendors(): void
    {
        $activeVendor = Vendor::factory()->create();
        $inactiveVendor = Vendor::factory()->pending()->create();

        $draftFeatured = Product::factory()->for($activeVendor)->featured()->create(['status' => 'draft']);
        $inactiveVendorFeatured = Product::factory()->for($inactiveVendor)->featured()->create(['status' => 'published']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Featured Products');
        $response->assertDontSee($draftFeatured->name);
        $response->assertDontSee($inactiveVendorFeatured->name);
    }

    public function test_searching_only_shows_published_products_from_active_vendors(): void
    {
        $activeVendor = Vendor::factory()->create();
        $inactiveVendor = Vendor::factory()->pending()->create();

        $published = Product::factory()->for($activeVendor)->create(['name' => 'Searchable Published Item', 'status' => 'published']);
        $draft = Product::factory()->for($activeVendor)->create(['name' => 'Searchable Draft Item', 'status' => 'draft']);
        $fromInactiveVendor = Product::factory()->for($inactiveVendor)->create(['name' => 'Searchable Inactive Vendor Item', 'status' => 'published']);

        $response = $this->get(route('home', ['search' => 'Searchable']));

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

    public function test_searching_hides_the_featured_section_and_shows_only_matching_results(): void
    {
        $vendor = Vendor::factory()->create();
        $featured = Product::factory()->for($vendor)->featured()->create(['name' => 'Featured Saree', 'status' => 'published']);
        $match = Product::factory()->for($vendor)->create(['name' => 'Cotton Kurti', 'status' => 'published']);

        $response = $this->get(route('home', ['search' => 'Kurti']));

        $response->assertOk();
        $response->assertDontSee('Featured Products');
        $response->assertSee($match->name);
        $response->assertDontSee($featured->name);
    }

    public function test_search_with_no_matches_shows_an_empty_state(): void
    {
        $vendor = Vendor::factory()->create();
        Product::factory()->for($vendor)->create(['name' => 'Cotton Kurti', 'status' => 'published']);

        $response = $this->get(route('home', ['search' => 'Nonexistent Item']));

        $response->assertOk();
        $response->assertSee('No products found.');
    }
}

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

    public function test_home_page_only_shows_published_products_from_active_vendors(): void
    {
        $activeVendor = Vendor::factory()->create();
        $inactiveVendor = Vendor::factory()->pending()->create();

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

    public function test_home_page_shows_featured_products(): void
    {
        $vendor = Vendor::factory()->create();
        $featured = Product::factory()->for($vendor)->featured()->create(['status' => 'published']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee($featured->name);
        $response->assertSee('Featured Products');
    }

    public function test_home_page_does_not_show_a_featured_section_when_nothing_is_featured(): void
    {
        $vendor = Vendor::factory()->create();
        Product::factory()->for($vendor)->create(['status' => 'published']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Featured Products');
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
}

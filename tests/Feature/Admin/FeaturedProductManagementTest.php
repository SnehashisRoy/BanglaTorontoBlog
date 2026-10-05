<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeaturedProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_guest_cannot_view_the_admin_product_list(): void
    {
        $response = $this->get(route('admin.products.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_view_the_admin_product_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.products.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_products_across_all_vendors(): void
    {
        Product::factory()->create(['name' => 'Fresh Mangoes']);

        $response = $this->actingAs($this->admin())->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSee('Fresh Mangoes');
    }

    public function test_admin_can_feature_a_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin())->patch(route('admin.products.feature', $product));

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_featured' => true]);
    }

    public function test_admin_can_unfeature_a_product(): void
    {
        $product = Product::factory()->featured()->create();

        $response = $this->actingAs($this->admin())->patch(route('admin.products.unfeature', $product));

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_featured' => false]);
    }

    public function test_non_admin_cannot_feature_a_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->patch(route('admin.products.feature', $product));

        $response->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_featured' => false]);
    }
}

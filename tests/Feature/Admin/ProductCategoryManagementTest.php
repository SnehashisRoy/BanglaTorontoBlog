<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_guest_cannot_view_the_category_list(): void
    {
        $response = $this->get(route('admin.product-categories.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_view_the_category_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.product-categories.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_the_category_list(): void
    {
        ProductCategory::factory()->create(['name' => 'Sarees']);

        $response = $this->actingAs($this->admin())->get(route('admin.product-categories.index'));

        $response->assertOk();
        $response->assertSee('Sarees');
    }

    public function test_admin_can_create_a_category_with_an_auto_generated_slug(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.product-categories.store'), [
            'name' => 'Home Decor',
        ]);

        $response->assertRedirect(route('admin.product-categories.index'));
        $this->assertDatabaseHas('product_categories', ['name' => 'Home Decor', 'slug' => 'home-decor']);
    }

    public function test_non_admin_cannot_create_a_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.product-categories.store'), [
            'name' => 'Home Decor',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('product_categories', ['name' => 'Home Decor']);
    }

    public function test_admin_can_update_a_category(): void
    {
        $category = ProductCategory::factory()->create(['name' => 'Sarees', 'slug' => 'sarees']);

        $response = $this->actingAs($this->admin())->put(route('admin.product-categories.update', $category), [
            'name' => 'Sarees & Lehengas',
        ]);

        $response->assertRedirect(route('admin.product-categories.index'));
        $this->assertDatabaseHas('product_categories', ['id' => $category->id, 'name' => 'Sarees & Lehengas']);
    }

    public function test_admin_can_delete_a_category(): void
    {
        $category = ProductCategory::factory()->create();

        $response = $this->actingAs($this->admin())->delete(route('admin.product-categories.destroy', $category));

        $response->assertRedirect(route('admin.product-categories.index'));
        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
    }

    public function test_deleting_a_category_leaves_its_products_intact(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->for($category, 'category')->create();

        $this->actingAs($this->admin())->delete(route('admin.product-categories.destroy', $category));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'product_category_id' => null]);
    }
}

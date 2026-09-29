<?php

namespace Tests\Feature\Vendor;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_create_a_product(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();

        $response = $this->actingAs($user)->post(route('vendor.products.store'), [
            'name' => 'Fresh Hilsa Fish',
            'status' => 'published',
            'price' => 24.99,
        ]);

        $response->assertRedirect(route('vendor.products.index'));
        $this->assertDatabaseHas('products', [
            'vendor_id' => $user->vendor->id,
            'name' => 'Fresh Hilsa Fish',
            'status' => 'published',
        ]);
    }

    public function test_vendor_can_see_only_their_own_products_in_the_dashboard(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();
        $other = Vendor::factory()->create();

        $mine = Product::factory()->for($user->vendor)->create();
        $theirs = Product::factory()->for($other)->create();

        $response = $this->actingAs($user)->get(route('vendor.products.index'));

        $response->assertOk();
        $response->assertSee($mine->name);
        $response->assertDontSee($theirs->name);
    }

    public function test_vendor_can_update_their_own_product(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();
        $product = Product::factory()->for($user->vendor)->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->put(route('vendor.products.update', $product), [
            'name' => 'New Name',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('vendor.products.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New Name']);
    }

    public function test_vendor_cannot_edit_another_vendors_product(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();
        $otherVendor = Vendor::factory()->create();
        $product = Product::factory()->for($otherVendor)->create();

        $response = $this->actingAs($user)->get(route('vendor.products.edit', $product));

        $response->assertForbidden();
    }

    public function test_vendor_cannot_update_another_vendors_product(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();
        $otherVendor = Vendor::factory()->create();
        $product = Product::factory()->for($otherVendor)->create(['name' => 'Original']);

        $response = $this->actingAs($user)->put(route('vendor.products.update', $product), [
            'name' => 'Hijacked',
            'status' => 'published',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Original']);
    }

    public function test_vendor_cannot_delete_another_vendors_product(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();
        $otherVendor = Vendor::factory()->create();
        $product = Product::factory()->for($otherVendor)->create();

        $response = $this->actingAs($user)->delete(route('vendor.products.destroy', $product));

        $response->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_vendor_can_delete_their_own_product(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();
        $product = Product::factory()->for($user->vendor)->create();

        $response = $this->actingAs($user)->delete(route('vendor.products.destroy', $product));

        $response->assertRedirect(route('vendor.products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_guest_cannot_manage_products(): void
    {
        $product = Product::factory()->for(Vendor::factory())->create();

        $this->get(route('vendor.products.index'))->assertRedirect(route('login'));
        $this->get(route('vendor.products.edit', $product))->assertRedirect(route('login'));
    }
}

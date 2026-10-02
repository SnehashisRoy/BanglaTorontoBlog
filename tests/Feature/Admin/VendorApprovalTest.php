<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_guest_cannot_view_the_vendor_list(): void
    {
        $response = $this->get(route('admin.vendors.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_view_the_vendor_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.vendors.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_the_vendor_list_with_both_statuses(): void
    {
        $pending = Vendor::factory()->pending()->create(['name' => 'Pending Shop']);
        $approved = Vendor::factory()->create(['name' => 'Approved Shop']);

        $response = $this->actingAs($this->admin())->get(route('admin.vendors.index'));

        $response->assertOk();
        $response->assertSee('Pending Shop');
        $response->assertSee('Approved Shop');
    }

    public function test_admin_can_approve_a_pending_vendor(): void
    {
        $vendor = Vendor::factory()->pending()->create();

        $response = $this->actingAs($this->admin())->patch(route('admin.vendors.approve', $vendor));

        $response->assertRedirect(route('admin.vendors.index'));
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => Vendor::STATUS_APPROVED]);
    }

    public function test_admin_can_move_an_approved_vendor_back_to_pending(): void
    {
        $vendor = Vendor::factory()->create();

        $response = $this->actingAs($this->admin())->patch(route('admin.vendors.mark-pending', $vendor));

        $response->assertRedirect(route('admin.vendors.index'));
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => Vendor::STATUS_PENDING]);
    }

    public function test_approving_a_vendor_makes_their_shop_and_products_publicly_visible(): void
    {
        $vendor = Vendor::factory()->pending()->create(['name' => 'Fresh Mart']);
        Product::factory()->for($vendor)->create(['name' => 'Fresh Mangoes', 'status' => 'published']);

        $this->get(route('shops.index'))->assertDontSee('Fresh Mart');
        $this->get(route('home'))->assertDontSee('Fresh Mangoes');

        $this->actingAs($this->admin())->patch(route('admin.vendors.approve', $vendor));

        $this->get(route('shops.index'))->assertSee('Fresh Mart');
        $this->get(route('home'))->assertSee('Fresh Mangoes');
    }

    public function test_non_admin_cannot_approve_a_vendor(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->pending()->create();

        $response = $this->actingAs($user)->patch(route('admin.vendors.approve', $vendor));

        $response->assertForbidden();
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => Vendor::STATUS_PENDING]);
    }
}

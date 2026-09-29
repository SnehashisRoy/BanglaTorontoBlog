<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('vendor.register'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_registration_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('vendor.register'));

        $response->assertOk();
    }

    public function test_authenticated_user_can_create_a_vendor_shop(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('vendor.register.store'), [
            'name' => 'Dhaka Grocers',
            'phone' => '416-555-0199',
            'address' => '500 Danforth Ave',
        ]);

        $response->assertRedirect(route('vendor.dashboard'));
        $this->assertDatabaseHas('vendors', [
            'user_id' => $user->id,
            'name' => 'Dhaka Grocers',
        ]);
    }

    public function test_a_user_cannot_create_a_second_vendor_shop(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();

        $response = $this->actingAs($user)->post(route('vendor.register.store'), [
            'name' => 'Second Shop',
            'phone' => '416-555-0199',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('vendors', 1);
    }

    public function test_vendor_dashboard_requires_a_shop(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('vendor.dashboard'));

        $response->assertForbidden();
    }
}

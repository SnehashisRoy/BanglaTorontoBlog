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
            'status' => Vendor::STATUS_PENDING,
        ]);
    }

    public function test_a_newly_registered_shop_is_not_publicly_visible_until_approved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('vendor.register.store'), [
            'name' => 'Dhaka Grocers',
            'phone' => '416-555-0199',
        ]);

        $vendor = Vendor::where('name', 'Dhaka Grocers')->first();

        $this->get(route('shops.index'))->assertDontSee('Dhaka Grocers');
        $this->get(route('shops.show', $vendor->slug))->assertNotFound();
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

    public function test_sell_intent_sends_a_guest_to_register_and_remembers_where_to_land(): void
    {
        $response = $this->get(route('vendor.intent', 'register'));

        $response->assertRedirect(route('register'));
        $response->assertSessionHas('url.intended', route('vendor.register'));
    }

    public function test_sell_intent_sends_a_guest_to_login_and_remembers_where_to_land(): void
    {
        $response = $this->get(route('vendor.intent', 'login'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('url.intended', route('vendor.register'));
    }

    public function test_sell_intent_sends_an_already_logged_in_user_straight_to_vendor_register(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('vendor.intent', 'register'));

        $response->assertRedirect(route('vendor.register'));
    }

    public function test_registering_via_the_sell_intent_link_lands_on_the_vendor_registration_form(): void
    {
        $this->get(route('vendor.intent', 'register'));

        $response = $this->post(route('register.store'), [
            'name' => 'Future Vendor',
            'email' => 'future-vendor@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('vendor.register'));
    }

    public function test_logging_in_via_the_sell_intent_link_lands_on_the_vendor_registration_form(): void
    {
        $user = User::factory()->create();
        $this->get(route('vendor.intent', 'login'));

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('vendor.register'));
    }
}

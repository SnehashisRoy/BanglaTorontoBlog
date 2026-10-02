<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_dashboard_has_a_logout_control(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();

        $response = $this->actingAs($user)->get(route('vendor.dashboard'));

        $response->assertOk();
        $response->assertSee(route('logout'), false);
    }
}

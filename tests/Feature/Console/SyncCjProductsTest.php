<?php

namespace Tests\Feature\Console;

use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorCjCredential;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncCjProductsTest extends TestCase
{
    use RefreshDatabase;

    private function connectedVendor(float $markup = 35): Vendor
    {
        $vendor = Vendor::factory()->cjEnabled()->create(['cj_markup_percent' => $markup]);
        VendorCjCredential::factory()->for($vendor)->create([
            'access_token_expires_at' => now()->addDays(100),
        ]);

        return $vendor;
    }

    public function test_it_updates_price_when_cj_cost_changed(): void
    {
        $vendor = $this->connectedVendor(35);
        $product = Product::factory()->for($vendor)->create(['price' => 11.34, 'status' => 'published']);
        $product->cjLink()->create([
            'cj_product_id' => '1001',
            'cj_variant_id' => 'v-red',
            'cj_cost_price' => 8.40,
            'cj_last_synced_at' => now()->subDay(),
        ]);

        Http::fake([
            '*/product/query*' => Http::response([
                'result' => true,
                'data' => [
                    'pid' => '1001',
                    'variants' => [
                        [
                            'vid' => 'v-red',
                            'variantKey' => 'Red',
                            'variantSellPrice' => '10.00', // cost went up
                            'inventories' => [['countryCode' => 'US', 'totalInventory' => 5]],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('cj:sync-products')->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'price' => 13.50, 'status' => 'published']);
        $this->assertDatabaseHas('cj_product_links', ['product_id' => $product->id, 'cj_cost_price' => 10.00]);
    }

    public function test_it_auto_drafts_a_product_that_is_now_out_of_stock(): void
    {
        $vendor = $this->connectedVendor();
        $product = Product::factory()->for($vendor)->create(['status' => 'published']);
        $product->cjLink()->create([
            'cj_product_id' => '1001',
            'cj_variant_id' => 'v-red',
            'cj_cost_price' => 8.40,
            'cj_last_synced_at' => now()->subDay(),
        ]);

        Http::fake([
            '*/product/query*' => Http::response([
                'result' => true,
                'data' => [
                    'pid' => '1001',
                    'variants' => [
                        [
                            'vid' => 'v-red',
                            'variantKey' => 'Red',
                            'variantSellPrice' => '8.40',
                            'inventories' => [['countryCode' => 'US', 'totalInventory' => 0]],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('cj:sync-products')->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'draft']);
    }

    public function test_it_batches_variants_of_the_same_cj_product_into_one_api_call(): void
    {
        $vendor = $this->connectedVendor();
        $red = Product::factory()->for($vendor)->create();
        $blue = Product::factory()->for($vendor)->create();

        $red->cjLink()->create(['cj_product_id' => '1001', 'cj_variant_id' => 'v-red', 'cj_cost_price' => 8.40, 'cj_last_synced_at' => now()]);
        $blue->cjLink()->create(['cj_product_id' => '1001', 'cj_variant_id' => 'v-blue', 'cj_cost_price' => 8.40, 'cj_last_synced_at' => now()]);

        Http::fake([
            '*/product/query*' => Http::response([
                'result' => true,
                'data' => [
                    'pid' => '1001',
                    'variants' => [
                        ['vid' => 'v-red', 'variantKey' => 'Red', 'variantSellPrice' => '8.40', 'inventories' => [['countryCode' => 'US', 'totalInventory' => 5]]],
                        ['vid' => 'v-blue', 'variantKey' => 'Blue', 'variantSellPrice' => '9.00', 'inventories' => [['countryCode' => 'US', 'totalInventory' => 5]]],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('cj:sync-products')->assertExitCode(Command::SUCCESS);

        Http::assertSentCount(1);
        $this->assertDatabaseHas('cj_product_links', ['product_id' => $blue->id, 'cj_cost_price' => 9.00]);
    }

    public function test_a_cj_failure_for_one_product_does_not_abort_the_rest(): void
    {
        $vendorA = $this->connectedVendor();
        $vendorB = $this->connectedVendor();

        $productA = Product::factory()->for($vendorA)->create();
        $productA->cjLink()->create(['cj_product_id' => 'bad-id', 'cj_variant_id' => 'v1', 'cj_cost_price' => 5, 'cj_last_synced_at' => now()]);

        $productB = Product::factory()->for($vendorB)->create();
        $productB->cjLink()->create(['cj_product_id' => 'good-id', 'cj_variant_id' => 'v2', 'cj_cost_price' => 5, 'cj_last_synced_at' => now()]);

        Http::fake([
            '*/product/query*' => function ($request) {
                if (str_contains($request->url(), 'bad-id')) {
                    return Http::response(['result' => false, 'message' => 'not found'], 200);
                }

                return Http::response([
                    'result' => true,
                    'data' => [
                        'pid' => 'good-id',
                        'variants' => [
                            ['vid' => 'v2', 'variantKey' => 'X', 'variantSellPrice' => '20.00', 'inventories' => [['countryCode' => 'US', 'totalInventory' => 5]]],
                        ],
                    ],
                ], 200);
            },
        ]);

        $this->artisan('cj:sync-products')->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('cj_product_links', ['product_id' => $productB->id, 'cj_cost_price' => 20.00]);
        $this->assertDatabaseHas('cj_product_links', ['product_id' => $productA->id, 'cj_cost_price' => 5.00]); // unchanged
    }

    public function test_vendors_without_cj_connected_are_skipped(): void
    {
        $vendor = Vendor::factory()->create(); // no CJ enabled/connected

        Http::fake();

        $this->artisan('cj:sync-products')->assertExitCode(Command::SUCCESS);

        Http::assertNothingSent();
    }
}

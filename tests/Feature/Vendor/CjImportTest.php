<?php

namespace Tests\Feature\Vendor;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCjCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CjImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Neutral FX rate so existing USD-based price assertions don't need
        // to account for currency conversion — that's covered separately in
        // CjCurrencyConverterTest. Seeding the cache avoids a real HTTP call.
        Cache::put('cj_usd_to_cad_rate', 1.0, now()->addDay());
    }

    private function connectedVendor(): Vendor
    {
        $vendor = Vendor::factory()->cjEnabled()->create(['cj_markup_percent' => 35]);
        VendorCjCredential::factory()->for($vendor)->create([
            'access_token_expires_at' => now()->addDays(100),
        ]);

        return $vendor;
    }

    public function test_cj_enabled_but_not_connected_vendor_cannot_search(): void
    {
        $user = User::factory()->has(Vendor::factory()->cjEnabled())->create();

        $response = $this->actingAs($user)->get(route('vendor.cj.import.search'));

        $response->assertForbidden();
    }

    public function test_a_connected_vendor_can_search_cj(): void
    {
        $vendor = $this->connectedVendor();

        Http::fake([
            '*/product/listV2*' => Http::response([
                'result' => true,
                'data' => [
                    'list' => [
                        [
                            'id' => '1001',
                            'nameEn' => 'Embroidered Cotton Saree',
                            'bigImage' => 'https://cj.example/img1.jpg',
                            'sellPrice' => '8.40',
                            'warehouseInventoryNum' => 50,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($vendor->user)->get(route('vendor.cj.import.search', ['keyword' => 'saree']));

        $response->assertOk();
        $response->assertSee('Embroidered Cotton Saree');
        $response->assertSee('11.34'); // 8.40 * 1.35
    }

    public function test_a_connected_vendor_can_view_variants_for_a_product(): void
    {
        $vendor = $this->connectedVendor();

        Http::fake([
            '*/product/query*' => Http::response([
                'result' => true,
                'data' => [
                    'pid' => '1001',
                    'productNameEn' => 'Embroidered Cotton Saree',
                    'description' => 'A nice saree.',
                    'sellPrice' => '8.40',
                    'variants' => [
                        [
                            'vid' => 'v-red',
                            'variantNameEn' => 'Saree Red',
                            'variantKey' => 'Red',
                            'variantSellPrice' => '8.40',
                            'inventories' => [['countryCode' => 'US', 'totalInventory' => 20]],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($vendor->user)->get(route('vendor.cj.import.show', '1001'));

        $response->assertOk();
        $response->assertSee('Red');
        $response->assertSee('In stock');
    }

    public function test_importing_a_variant_creates_a_draft_product_with_a_cj_link(): void
    {
        Storage::fake('public');
        $vendor = $this->connectedVendor();

        Http::fake([
            '*/product/query*' => Http::response([
                'result' => true,
                'data' => [
                    'pid' => '1001',
                    'productNameEn' => 'Embroidered Cotton Saree',
                    'description' => 'A nice saree.',
                    'bigImage' => 'https://cj.example/img1.jpg',
                    'sellPrice' => '8.40',
                    'variants' => [
                        [
                            'vid' => 'v-red',
                            'variantNameEn' => 'Saree Red',
                            'variantKey' => 'Red',
                            'variantSellPrice' => '8.40',
                            'inventories' => [['countryCode' => 'US', 'totalInventory' => 20]],
                        ],
                        [
                            'vid' => 'v-green',
                            'variantNameEn' => 'Saree Green',
                            'variantKey' => 'Green',
                            'variantSellPrice' => '9.00',
                            'inventories' => [['countryCode' => 'US', 'totalInventory' => 0]],
                        ],
                    ],
                ],
            ], 200),
            'https://cj.example/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $response = $this->actingAs($vendor->user)->post(route('vendor.cj.import.store', '1001'), [
            'variant_ids' => ['v-red'],
        ]);

        $response->assertRedirect(route('vendor.products.index'));

        $product = Product::where('vendor_id', $vendor->id)->first();
        $this->assertNotNull($product);
        $this->assertSame('Embroidered Cotton Saree — Red', $product->name);
        $this->assertSame('draft', $product->status);
        $this->assertSame('11.34', (string) $product->price); // 8.40 * 1.35

        $this->assertDatabaseHas('cj_product_links', [
            'product_id' => $product->id,
            'cj_product_id' => '1001',
            'cj_variant_id' => 'v-red',
        ]);
    }

    public function test_out_of_stock_variants_cannot_be_imported_even_if_requested(): void
    {
        Storage::fake('public');
        $vendor = $this->connectedVendor();

        Http::fake([
            '*/product/query*' => Http::response([
                'result' => true,
                'data' => [
                    'pid' => '1001',
                    'productNameEn' => 'Embroidered Cotton Saree',
                    'sellPrice' => '8.40',
                    'variants' => [
                        [
                            'vid' => 'v-green',
                            'variantKey' => 'Green',
                            'variantSellPrice' => '9.00',
                            'inventories' => [['countryCode' => 'US', 'totalInventory' => 0]],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($vendor->user)->post(route('vendor.cj.import.store', '1001'), [
            'variant_ids' => ['v-green'],
        ]);

        $this->assertDatabaseCount('products', 0);
    }

    /**
     * Confirmed against a real CJ account: CJ can return `inventories: null`
     * for a variant whose product listing clearly has stock. That must not
     * block the import — only a confirmed zero should.
     */
    public function test_a_variant_with_unknown_stock_can_still_be_imported(): void
    {
        Storage::fake('public');
        $vendor = $this->connectedVendor();

        Http::fake([
            '*/product/query*' => Http::response([
                'result' => true,
                'data' => [
                    'pid' => '1001',
                    'productNameEn' => 'Embroidered Cotton Saree',
                    'sellPrice' => '8.40',
                    'variants' => [
                        [
                            'vid' => 'v-red',
                            'variantKey' => 'Red',
                            'variantSellPrice' => '8.40',
                            'inventories' => null,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($vendor->user)->post(route('vendor.cj.import.store', '1001'), [
            'variant_ids' => ['v-red'],
        ]);

        $this->assertDatabaseCount('products', 1);
    }

    public function test_a_vendor_cannot_import_without_cj_connected(): void
    {
        $user = User::factory()->has(Vendor::factory()->cjEnabled())->create();

        $response = $this->actingAs($user)->post(route('vendor.cj.import.store', '1001'), [
            'variant_ids' => ['v-red'],
        ]);

        $response->assertForbidden();
    }
}

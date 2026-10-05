<?php

namespace Tests\Feature;

use App\Models\VendorCjCredential;
use App\Services\CjApiException;
use App\Services\CjDropshippingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CjDropshippingClientTest extends TestCase
{
    use RefreshDatabase;

    private function client(): CjDropshippingClient
    {
        return new CjDropshippingClient('https://developers.cjdropshipping.com/api2.0/v1');
    }

    public function test_connect_exchanges_an_api_key_for_a_token_pair(): void
    {
        Http::fake([
            '*/authentication/getAccessToken' => Http::response([
                'code' => 200,
                'result' => true,
                'success' => true,
                'message' => 'Success',
                'data' => [
                    'accessToken' => 'access-123',
                    'accessTokenExpiryDate' => now()->addDays(180)->toIso8601String(),
                    'refreshToken' => 'refresh-123',
                    'refreshTokenExpiryDate' => now()->addDays(180)->toIso8601String(),
                    'openId' => 999,
                    'createDate' => now()->toIso8601String(),
                ],
            ], 200),
        ]);

        $pair = $this->client()->connect('vendor-supplied-api-key');

        $this->assertSame('access-123', $pair->accessToken);
        $this->assertSame('refresh-123', $pair->refreshToken);

        Http::assertSent(fn ($request) => $request['apiKey'] === 'vendor-supplied-api-key');
    }

    public function test_connect_throws_on_a_business_logic_failure_even_with_http_200(): void
    {
        Http::fake([
            '*/authentication/getAccessToken' => Http::response([
                'code' => 400,
                'result' => false,
                'success' => false,
                'message' => 'Invalid apiKey',
            ], 200),
        ]);

        $this->expectException(CjApiException::class);
        $this->expectExceptionMessage('Invalid apiKey');

        $this->client()->connect('bad-key');
    }

    public function test_connect_throws_on_a_transport_failure(): void
    {
        Http::fake([
            '*/authentication/getAccessToken' => Http::response(['message' => 'server error'], 500),
        ]);

        $this->expectException(CjApiException::class);

        $this->client()->connect('any-key');
    }

    public function test_access_token_for_returns_the_stored_token_without_refreshing_when_not_expiring(): void
    {
        $credential = VendorCjCredential::factory()->create([
            'access_token' => 'still-good',
            'access_token_expires_at' => now()->addDays(100),
        ]);

        Http::fake(); // nothing faked — a real request here would error the test

        $token = $this->client()->accessTokenFor($credential);

        $this->assertSame('still-good', $token);
        Http::assertNothingSent();
    }

    public function test_access_token_for_refreshes_and_persists_when_close_to_expiring(): void
    {
        $credential = VendorCjCredential::factory()->create([
            'access_token' => 'about-to-expire',
            'refresh_token' => 'old-refresh',
            'access_token_expires_at' => now()->addMinutes(5),
        ]);

        Http::fake([
            '*/authentication/refreshAccessToken' => Http::response([
                'result' => true,
                'data' => [
                    'accessToken' => 'fresh-access',
                    'accessTokenExpiryDate' => now()->addDays(180)->toIso8601String(),
                    'refreshToken' => 'fresh-refresh',
                    'refreshTokenExpiryDate' => now()->addDays(180)->toIso8601String(),
                ],
            ], 200),
        ]);

        $token = $this->client()->accessTokenFor($credential);

        $this->assertSame('fresh-access', $token);
        $this->assertSame('fresh-access', $credential->refresh()->access_token);
        Http::assertSent(fn ($request) => $request['refreshToken'] === 'old-refresh');
    }

    /**
     * Shape confirmed against a real CJ account on 2026-10-04 — CJ's own docs
     * describe a flat data.list, but the live API actually nests results as
     * data.content[0].productList.
     */
    public function test_search_sends_the_access_token_header_and_maps_results(): void
    {
        $credential = VendorCjCredential::factory()->create([
            'access_token' => 'token-abc',
            'access_token_expires_at' => now()->addDays(100),
        ]);

        Http::fake([
            '*/product/listV2*' => Http::response([
                'result' => true,
                'data' => [
                    'content' => [
                        [
                            'productList' => [
                                [
                                    'id' => '1001',
                                    'nameEn' => 'Embroidered Cotton Saree',
                                    'bigImage' => 'https://cj.example/img1.jpg',
                                    'sellPrice' => '8.40',
                                    'categoryId' => 'cat-1',
                                    'warehouseInventoryNum' => 50,
                                ],
                            ],
                            'keyWord' => 'saree',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $results = $this->client()->search($credential, ['keyWord' => 'saree']);

        $this->assertCount(1, $results->items);
        $this->assertSame('1001', $results->items[0]->id);
        $this->assertSame('Embroidered Cotton Saree', $results->items[0]->name);
        $this->assertSame(8.40, $results->items[0]->sellPrice);

        Http::assertSent(fn ($request) => $request->hasHeader('CJ-Access-Token', 'token-abc'));
    }

    public function test_search_falls_back_to_a_flat_list_shape_if_cj_ever_returns_one(): void
    {
        $credential = VendorCjCredential::factory()->create([
            'access_token' => 'token-abc',
            'access_token_expires_at' => now()->addDays(100),
        ]);

        Http::fake([
            '*/product/listV2*' => Http::response([
                'result' => true,
                'data' => [
                    'list' => [
                        ['id' => '2002', 'nameEn' => 'Fallback Shape Item', 'sellPrice' => '5.00'],
                    ],
                ],
            ], 200),
        ]);

        $results = $this->client()->search($credential, ['keyWord' => 'anything']);

        $this->assertCount(1, $results->items);
        $this->assertSame('2002', $results->items[0]->id);
    }

    public function test_search_exposes_pagination_metadata_and_clamps_page_size(): void
    {
        $credential = VendorCjCredential::factory()->create([
            'access_token' => 'token-abc',
            'access_token_expires_at' => now()->addDays(100),
        ]);

        Http::fake([
            '*/product/listV2*' => Http::response([
                'result' => true,
                'data' => [
                    'pageNumber' => 2,
                    'totalPages' => 61,
                    'totalRecords' => 1216,
                    'content' => [['productList' => []]],
                ],
            ], 200),
        ]);

        $results = $this->client()->search($credential, ['keyWord' => 'jewelry'], page: 2, size: 500);

        $this->assertSame(2, $results->page);
        $this->assertSame(61, $results->totalPages);
        $this->assertSame(1216, $results->totalRecords);
        $this->assertTrue($results->hasPrevious());
        $this->assertTrue($results->hasNext());

        // size was clamped to CJ's documented max (100), not sent as 500.
        Http::assertSent(fn ($request) => $request['size'] === 100 && $request['page'] === 2);
    }

    public function test_product_detail_maps_variants_and_picks_an_in_stock_warehouse(): void
    {
        $credential = VendorCjCredential::factory()->create([
            'access_token' => 'token-abc',
            'access_token_expires_at' => now()->addDays(100),
        ]);

        Http::fake([
            '*/product/query*' => Http::response([
                'result' => true,
                'data' => [
                    'pid' => '1001',
                    'productNameEn' => 'Embroidered Cotton Saree',
                    'bigImage' => 'https://cj.example/img1.jpg',
                    'description' => 'A nice saree.',
                    'sellPrice' => '8.40',
                    'variants' => [
                        [
                            'vid' => 'v-red',
                            'variantNameEn' => 'Saree Red',
                            'variantKey' => 'Red',
                            'variantSellPrice' => '8.40',
                            'inventories' => [
                                ['countryCode' => 'CN', 'totalInventory' => 0],
                                ['countryCode' => 'US', 'totalInventory' => 20],
                            ],
                        ],
                        [
                            'vid' => 'v-blue',
                            'variantNameEn' => 'Saree Blue',
                            'variantKey' => 'Blue',
                            'variantSellPrice' => '9.10',
                            'inventories' => [
                                ['countryCode' => 'CN', 'totalInventory' => 0],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $detail = $this->client()->productDetail($credential, '1001');

        $this->assertSame('1001', $detail->pid);
        $this->assertCount(2, $detail->variants);

        $red = $detail->variants[0];
        $this->assertSame('US', $red->warehouseCountry);
        $this->assertSame(20, $red->totalInventory);
        $this->assertTrue($red->inStock());

        $blue = $detail->variants[1];
        $this->assertFalse($blue->inStock());
    }

    /**
     * Confirmed against a real CJ account (2026-10-04): `inventories` can
     * come back null even for a product whose search listing reports stock.
     * That must not be treated as "confirmed zero" — only an explicit zero
     * should block import.
     */
    public function test_a_variant_with_null_inventories_is_treated_as_stock_unknown_not_out_of_stock(): void
    {
        $credential = VendorCjCredential::factory()->create([
            'access_token' => 'token-abc',
            'access_token_expires_at' => now()->addDays(100),
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
                            'variantSellPrice' => 16.70,
                            'inventories' => null,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $variant = $this->client()->productDetail($credential, '1001')->variants[0];

        $this->assertNull($variant->totalInventory);
        $this->assertFalse($variant->stockKnown());
        $this->assertTrue($variant->inStock());
    }

    /**
     * CJ products carry more than one photo: productImageSet (a real gallery
     * array) and a per-variant image, plus CJ descriptions can embed their
     * own illustrative <img> tags — none of that should be lost.
     */
    public function test_product_detail_collects_the_gallery_and_description_images(): void
    {
        $credential = VendorCjCredential::factory()->create([
            'access_token' => 'token-abc',
            'access_token_expires_at' => now()->addDays(100),
        ]);

        Http::fake([
            '*/product/query*' => Http::response([
                'result' => true,
                'data' => [
                    'pid' => '1001',
                    'productNameEn' => 'Shirt',
                    'sellPrice' => '16.70',
                    'bigImage' => 'https://cf.example/main.jpg',
                    'productImageSet' => ['https://cf.example/main.jpg', 'https://cf.example/gallery-2.jpg'],
                    'description' => '<p>Detail:<br/><img src="https://cf.example/desc.jpg"/></p>',
                    'variants' => [
                        [
                            'vid' => 'v-red',
                            'variantKey' => 'Red',
                            'variantSellPrice' => '16.70',
                            'variantImage' => 'https://cf.example/variant-red.jpg',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $detail = $this->client()->productDetail($credential, '1001');

        $this->assertSame('https://cf.example/main.jpg', $detail->imageUrl);
        $this->assertSame(['https://cf.example/main.jpg', 'https://cf.example/gallery-2.jpg'], $detail->imageUrls);
        $this->assertSame(['https://cf.example/desc.jpg'], $detail->descriptionImageUrls);
        $this->assertSame('https://cf.example/variant-red.jpg', $detail->variants[0]->imageUrl);
    }

    public function test_product_image_set_falls_back_to_big_image_when_cj_omits_the_gallery(): void
    {
        $credential = VendorCjCredential::factory()->create([
            'access_token' => 'token-abc',
            'access_token_expires_at' => now()->addDays(100),
        ]);

        Http::fake([
            '*/product/query*' => Http::response([
                'result' => true,
                'data' => [
                    'pid' => '1001',
                    'bigImage' => 'https://cf.example/only.jpg',
                    'sellPrice' => '16.70',
                    'variants' => [],
                ],
            ], 200),
        ]);

        $detail = $this->client()->productDetail($credential, '1001');

        $this->assertSame(['https://cf.example/only.jpg'], $detail->imageUrls);
    }
}

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
                    'list' => [
                        [
                            'id' => '1001',
                            'nameEn' => 'Embroidered Cotton Saree',
                            'bigImage' => 'https://cj.example/img1.jpg',
                            'sellPrice' => '8.40',
                            'categoryId' => 'cat-1',
                            'warehouseInventoryNum' => 50,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $results = $this->client()->search($credential, ['keyWord' => 'saree']);

        $this->assertCount(1, $results);
        $this->assertSame('1001', $results[0]->id);
        $this->assertSame('Embroidered Cotton Saree', $results[0]->name);
        $this->assertSame(8.40, $results[0]->sellPrice);

        Http::assertSent(fn ($request) => $request->hasHeader('CJ-Access-Token', 'token-abc'));
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
}

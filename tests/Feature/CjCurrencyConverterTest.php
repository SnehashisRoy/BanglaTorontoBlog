<?php

namespace Tests\Feature;

use App\Services\CjCurrencyConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CjCurrencyConverterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_and_applies_the_live_rate(): void
    {
        Http::fake([
            'api.frankfurter.app/*' => Http::response(['rates' => ['CAD' => 1.35]], 200),
        ]);

        $converter = app(CjCurrencyConverter::class);

        $this->assertSame(1.35, $converter->rate());
        $this->assertSame(13.50, $converter->toCad(10.00));
    }

    public function test_selling_price_converts_then_applies_markup(): void
    {
        Http::fake([
            'api.frankfurter.app/*' => Http::response(['rates' => ['CAD' => 1.35]], 200),
        ]);

        $converter = app(CjCurrencyConverter::class);

        // $10 USD -> $13.50 CAD, then +35% markup -> $18.225 -> rounded $18.23
        $this->assertSame(18.23, $converter->sellingPrice(10.00, 35));
    }

    public function test_the_rate_is_cached_so_only_one_http_call_is_made(): void
    {
        Http::fake([
            'api.frankfurter.app/*' => Http::response(['rates' => ['CAD' => 1.35]], 200),
        ]);

        $converter = app(CjCurrencyConverter::class);

        $converter->rate();
        $converter->rate();
        $converter->toCad(5);

        Http::assertSentCount(1);
    }

    public function test_it_falls_back_to_the_configured_rate_when_the_api_fails(): void
    {
        config(['services.cj_dropshipping.usd_to_cad_fallback_rate' => 1.40]);

        Http::fake([
            'api.frankfurter.app/*' => Http::response(null, 500),
        ]);

        $converter = app(CjCurrencyConverter::class);

        $this->assertSame(1.40, $converter->rate());
    }

    public function test_it_falls_back_when_the_response_is_missing_the_cad_rate(): void
    {
        config(['services.cj_dropshipping.usd_to_cad_fallback_rate' => 1.40]);

        Http::fake([
            'api.frankfurter.app/*' => Http::response(['rates' => ['EUR' => 0.9]], 200),
        ]);

        $converter = app(CjCurrencyConverter::class);

        $this->assertSame(1.40, $converter->rate());
    }

    public function test_a_pre_seeded_cache_value_skips_the_http_call_entirely(): void
    {
        Cache::put('cj_usd_to_cad_rate', 2.0, now()->addDay());

        Http::fake(); // any real request here would fail the assertion below

        $converter = app(CjCurrencyConverter::class);

        $this->assertSame(2.0, $converter->rate());
        Http::assertNothingSent();
    }
}

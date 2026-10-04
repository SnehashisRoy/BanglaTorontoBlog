<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CJ Dropshipping always quotes prices in USD (confirmed — no currency
 * parameter exists on their API). This converts to CAD for display/storage,
 * since every other price on the site is implicitly CAD. The live rate is
 * fetched from a free, no-API-key exchange rate service and cached for a day;
 * if that ever fails with nothing cached yet, a configured fallback rate is
 * used instead of breaking pricing entirely.
 */
class CjCurrencyConverter
{
    private const CACHE_KEY = 'cj_usd_to_cad_rate';

    public function rate(): float
    {
        return Cache::remember(self::CACHE_KEY, now()->addDay(), function () {
            try {
                $response = Http::timeout(5)->get('https://api.frankfurter.app/latest', [
                    'from' => 'USD',
                    'to' => 'CAD',
                ]);

                $rate = $response->json('rates.CAD');

                if ($response->ok() && is_numeric($rate)) {
                    return (float) $rate;
                }
            } catch (\Throwable $e) {
                Log::warning('USD→CAD rate lookup failed, using fallback', ['exception' => $e->getMessage()]);
            }

            return (float) config('services.cj_dropshipping.usd_to_cad_fallback_rate');
        });
    }

    public function toCad(float $usdAmount): float
    {
        return $usdAmount * $this->rate();
    }

    /**
     * The full pricing formula in one place: USD cost → CAD → vendor's markup.
     */
    public function sellingPrice(float $usdCost, float $markupPercent): float
    {
        return round($this->toCad($usdCost) * (1 + $markupPercent / 100), 2);
    }
}

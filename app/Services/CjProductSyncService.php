<?php

namespace App\Services;

use App\Dto\CjVariant;
use App\Models\Product;
use App\Models\Vendor;

/**
 * Keeps CJ-imported products' prices in line with the vendor's markup and
 * (via the daily sync command) with CJ's own current cost/stock.
 */
class CjProductSyncService
{
    /**
     * Recompute every CJ-linked product's price for this vendor from its
     * last-known CJ cost and the vendor's *current* markup — used right after
     * a vendor changes their markup %, so the change takes effect immediately
     * rather than waiting for the next scheduled resync. No CJ API call needed.
     */
    public function repriceForVendor(Vendor $vendor): void
    {
        Product::where('vendor_id', $vendor->id)
            ->whereHas('cjLink')
            ->with('cjLink')
            ->get()
            ->each(fn (Product $product) => $this->applyMarkup($product, $vendor));
    }

    /**
     * Apply a freshly-fetched CJ variant's cost/stock to its linked local
     * product: update cost/warehouse/sync time, reprice if still in stock,
     * or auto-draft it if CJ no longer has it — used by the daily sync command.
     */
    public function applyVariantSync(Product $product, Vendor $vendor, CjVariant $variant): void
    {
        $product->cjLink->update([
            'cj_cost_price' => $variant->sellPrice,
            'cj_warehouse_country' => $variant->warehouseCountry,
            'cj_last_synced_at' => now(),
        ]);

        if (! $variant->inStock()) {
            $product->update(['status' => 'draft']);

            return;
        }

        $this->applyMarkup($product, $vendor);
    }

    private function applyMarkup(Product $product, Vendor $vendor): void
    {
        $markup = (float) ($vendor->cj_markup_percent ?? 0);
        $cost = (float) $product->cjLink->cj_cost_price;

        $product->update([
            'price' => round($cost * (1 + $markup / 100), 2),
        ]);
    }
}

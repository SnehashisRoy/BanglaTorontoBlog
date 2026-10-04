<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Vendor;
use App\Services\CjApiException;
use App\Services\CjDropshippingClient;
use App\Services\CjProductSyncService;
use Illuminate\Console\Command;

class SyncCjProducts extends Command
{
    protected $signature = 'cj:sync-products';

    protected $description = 'Resync price and stock for every CJ-imported product from CJ Dropshipping, one product/variant at a time; auto-drafts anything CJ no longer has in stock';

    public function handle(CjDropshippingClient $client, CjProductSyncService $sync): int
    {
        $vendors = Vendor::where('cj_dropshipping_enabled', true)
            ->whereHas('cjCredential')
            ->with('cjCredential')
            ->get();

        if ($vendors->isEmpty()) {
            $this->info('No vendors have CJ Dropshipping connected — nothing to sync.');

            return Command::SUCCESS;
        }

        $updated = 0;
        $outOfStock = 0;
        $failed = 0;

        foreach ($vendors as $vendor) {
            $products = Product::where('vendor_id', $vendor->id)
                ->whereHas('cjLink')
                ->with('cjLink')
                ->get();

            if ($products->isEmpty()) {
                continue;
            }

            $this->line("=== {$vendor->name} ({$products->count()} CJ product(s)) ===");

            // Group by CJ product ID so one variant-bearing product only costs
            // one productDetail call, no matter how many of its variants this
            // vendor imported.
            $byCjProductId = [];

            foreach ($products as $product) {
                $byCjProductId[$product->cjLink->cj_product_id][] = $product;
            }

            foreach ($byCjProductId as $cjProductId => $localProducts) {
                try {
                    $detail = $client->productDetail($vendor->cjCredential, $cjProductId);
                } catch (CjApiException $e) {
                    report($e);
                    $this->error("  CJ product {$cjProductId}: {$e->getMessage()}");
                    $failed += count($localProducts);

                    continue;
                }

                foreach ($localProducts as $product) {
                    $variant = null;

                    foreach ($detail->variants as $candidate) {
                        if ($candidate->id === $product->cjLink->cj_variant_id) {
                            $variant = $candidate;

                            break;
                        }
                    }

                    if (! $variant) {
                        $this->line("  [{$product->name}] variant no longer exists on CJ — left as-is.");

                        continue;
                    }

                    $sync->applyVariantSync($product, $vendor, $variant);

                    if ($variant->inStock()) {
                        $this->line("  ✓ [{$product->name}] synced — cost \${$variant->sellPrice}");
                        $updated++;
                    } else {
                        $this->line("  ⏸ [{$product->name}] out of stock on CJ — moved to draft.");
                        $outOfStock++;
                    }
                }
            }
        }

        $this->line('');
        $this->info("Done. Synced: {$updated}, auto-drafted (out of stock): {$outOfStock}, failed: {$failed}.");

        return Command::SUCCESS;
    }
}

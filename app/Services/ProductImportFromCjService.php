<?php

namespace App\Services;

use App\Dto\CjProductDetail;
use App\Dto\CjVariant;
use App\Models\Product;
use App\Models\Vendor;
use App\Repositories\ProductRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Creates one local Product (as a draft) per imported CJ variant, reusing the
 * same ProductRepository the manual/AI-suggestion flows use — so a CJ-imported
 * product is a completely ordinary product afterward, just with a cj_links row
 * tying it back to its source for later resyncing.
 */
class ProductImportFromCjService
{
    public function __construct(private readonly ProductRepository $products) {}

    public function importVariant(Vendor $vendor, CjProductDetail $product, CjVariant $variant): Product
    {
        $markup = (float) ($vendor->cj_markup_percent ?? 0);
        $price = round($variant->sellPrice * (1 + $markup / 100), 2);
        $name = $variant->key !== '' ? "{$product->name} — {$variant->key}" : $product->name;

        $newProduct = $this->products->createForVendor($vendor, [
            'name' => $name,
            'description' => $product->description,
            'price' => $price,
            'status' => 'draft',
        ]);

        if ($product->imageUrl && ($file = $this->downloadAsUploadedFile($product->imageUrl))) {
            $this->products->syncImages($newProduct, [$file]);
        }

        $newProduct->cjLink()->create([
            'cj_product_id' => $product->pid,
            'cj_variant_id' => $variant->id,
            'cj_cost_price' => $variant->sellPrice,
            'cj_warehouse_country' => $variant->warehouseCountry,
            'cj_last_synced_at' => now(),
        ]);

        return $newProduct;
    }

    /**
     * Image download failures are non-fatal — the listing is still useful
     * without a photo, and the vendor can add one manually afterward.
     */
    private function downloadAsUploadedFile(string $url): ?UploadedFile
    {
        try {
            $response = Http::timeout(15)->get($url);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $extension = Str::lower(pathinfo(is_string($path) ? $path : '', PATHINFO_EXTENSION)) ?: 'jpg';
        $tmpPath = tempnam(sys_get_temp_dir(), 'cj_img_');
        file_put_contents($tmpPath, $response->body());

        return new UploadedFile($tmpPath, "cj-image.{$extension}", $response->header('Content-Type') ?: 'image/jpeg', null, true);
    }
}

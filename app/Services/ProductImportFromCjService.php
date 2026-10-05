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
    /**
     * Matches the max photo count the manual upload form already enforces
     * (StoreProductRequest), so an import can't exceed what the product
     * edit form itself would ever allow.
     */
    private const MAX_IMAGES = 8;

    public function __construct(
        private readonly ProductRepository $products,
        private readonly CjCurrencyConverter $currency,
    ) {}

    public function importVariant(Vendor $vendor, CjProductDetail $product, CjVariant $variant): Product
    {
        $markup = (float) ($vendor->cj_markup_percent ?? 0);
        $price = $this->currency->sellingPrice($variant->sellPrice, $markup);
        $name = $variant->key !== '' ? "{$product->name} — {$variant->key}" : $product->name;

        $newProduct = $this->products->createForVendor($vendor, [
            'name' => $name,
            'description' => $this->cleanDescription($product->description),
            'price' => $price,
            'status' => 'draft',
        ]);

        $files = array_filter(array_map(
            fn (string $url) => $this->downloadAsUploadedFile($url),
            $this->imageUrlsFor($product, $variant),
        ));

        if (! empty($files)) {
            $this->products->syncImages($newProduct, $files);
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
     * All the images worth pulling in for this variant, in priority order:
     * the variant's own photo (most specific — e.g. the actual red one, not
     * a generic shot), then CJ's product gallery, then any images that were
     * embedded inside the description itself (confirmed real — CJ
     * descriptions can contain illustrative <img> tags). Deduplicated and
     * capped so an import can never exceed what the product form allows.
     *
     * @return array<string>
     */
    private function imageUrlsFor(CjProductDetail $product, CjVariant $variant): array
    {
        $urls = array_filter([
            $variant->imageUrl,
            ...$product->imageUrls,
            ...$product->descriptionImageUrls,
        ]);

        return array_slice(array_values(array_unique($urls)), 0, self::MAX_IMAGES);
    }

    /**
     * CJ's description comes back as HTML (`<p>`, `&nbsp;`, even embedded
     * `<img>` tags) — confirmed on a real import. Every other product
     * description in this app (manually typed, or AI-suggested) is plain
     * text, rendered with `{{ }}` (which escapes HTML, showing literal tags
     * rather than formatting them) — so this converts rather than special-
     * casing CJ-sourced products' rendering everywhere else.
     */
    private function cleanDescription(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $text = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $text = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        $text = str_replace("\u{00A0}", ' ', $text); // non-breaking space -> plain space
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = trim($text);

        return $text !== '' ? $text : null;
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

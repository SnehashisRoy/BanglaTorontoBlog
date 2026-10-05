<?php

namespace App\Dto;

use Illuminate\Support\Facades\Log;

/**
 * Full detail for one CJ product, from product/query, including its variants.
 *
 * See CjProductSummary's note — CJ's documented field names don't always
 * match what their API actually returns, so a short list of plausible
 * alternates is tried and a genuine mismatch is logged, not thrown.
 */
final class CjProductDetail
{
    /**
     * @param  array<string>  $imageUrls  CJ's full photo gallery for this product
     *                                    (productImageSet) — more than just the one "main" image.
     * @param  array<string>  $descriptionImageUrls  images found embedded inside
     *                                               the HTML description itself (confirmed real — CJ descriptions can
     *                                               contain illustrative <img> tags, e.g. a close-up of a collar style).
     *                                               Captured separately *before* the description is stripped to plain
     *                                               text, so they aren't silently discarded.
     * @param  array<CjVariant>  $variants
     */
    public function __construct(
        public readonly string $pid,
        public readonly string $name,
        public readonly ?string $imageUrl,
        public readonly array $imageUrls,
        public readonly ?string $description,
        public readonly array $descriptionImageUrls,
        public readonly float $sellPrice,
        public readonly array $variants,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $pid = $data['pid'] ?? $data['id'] ?? $data['productId'] ?? null;

        if ($pid === null) {
            Log::warning('CJ product detail had no recognizable pid field', ['data' => $data]);
        }

        $imageUrl = $data['bigImage'] ?? null;

        // productImageSet is the real gallery array. (CJ's similarly-named
        // "productImage" field is, in practice, a JSON-encoded *string* of
        // the same list, not a real array — deliberately not used here.)
        $imageUrls = array_values(array_filter(
            is_array($data['productImageSet'] ?? null) ? $data['productImageSet'] : [],
            fn ($url) => is_string($url) && $url !== '',
        ));

        if (empty($imageUrls) && is_string($imageUrl) && $imageUrl !== '') {
            $imageUrls = [$imageUrl];
        }

        $imageUrl ??= $imageUrls[0] ?? null;
        $description = $data['description'] ?? null;

        return new self(
            pid: (string) ($pid ?? ''),
            name: $data['productNameEn'] ?? $data['nameEn'] ?? $data['productName'] ?? '',
            imageUrl: $imageUrl,
            imageUrls: $imageUrls,
            description: $description,
            descriptionImageUrls: self::extractImageUrls($description),
            sellPrice: (float) ($data['sellPrice'] ?? 0),
            variants: array_map(
                fn (array $variant) => CjVariant::fromArray($variant),
                $data['variants'] ?? [],
            ),
        );
    }

    /**
     * @return array<string>
     */
    private static function extractImageUrls(?string $html): array
    {
        if ($html === null || $html === '') {
            return [];
        }

        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches);

        return array_values(array_unique($matches[1]));
    }
}

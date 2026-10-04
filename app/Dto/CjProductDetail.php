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
     * @param  array<CjVariant>  $variants
     */
    public function __construct(
        public readonly string $pid,
        public readonly string $name,
        public readonly ?string $imageUrl,
        public readonly ?string $description,
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

        return new self(
            pid: (string) ($pid ?? ''),
            name: $data['productNameEn'] ?? $data['nameEn'] ?? $data['productName'] ?? '',
            imageUrl: $data['bigImage'] ?? $data['productImage'] ?? null,
            description: $data['description'] ?? null,
            sellPrice: (float) ($data['sellPrice'] ?? 0),
            variants: array_map(
                fn (array $variant) => CjVariant::fromArray($variant),
                $data['variants'] ?? [],
            ),
        );
    }
}

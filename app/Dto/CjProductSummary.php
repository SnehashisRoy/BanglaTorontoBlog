<?php

namespace App\Dto;

use Illuminate\Support\Facades\Log;

/**
 * One row from CJ's product/listV2 search results.
 *
 * CJ's own docs say this row's id field is "id", but that didn't match a real
 * response seen in practice (empty/missing) — so several plausible key names
 * are tried, and a mismatch is logged rather than thrown, since one malformed
 * row shouldn't take down the whole search results page.
 */
final class CjProductSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $imageUrl,
        public readonly float $sellPrice,
        public readonly ?string $categoryId,
        public readonly int $warehouseInventoryNum,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? $data['pid'] ?? $data['productId'] ?? null;

        if ($id === null) {
            Log::warning('CJ product search result had no recognizable id field', ['data' => $data]);
        }

        return new self(
            id: (string) ($id ?? ''),
            name: $data['nameEn'] ?? $data['productNameEn'] ?? $data['productName'] ?? '',
            imageUrl: $data['bigImage'] ?? $data['productImage'] ?? null,
            sellPrice: (float) ($data['sellPrice'] ?? 0),
            categoryId: $data['categoryId'] ?? null,
            warehouseInventoryNum: (int) ($data['warehouseInventoryNum'] ?? 0),
        );
    }
}

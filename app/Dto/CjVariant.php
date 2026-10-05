<?php

namespace App\Dto;

use Illuminate\Support\Facades\Log;

/**
 * One SKU variant of a CJ product (e.g. a specific color/size combination),
 * from the "variants" array of product/query.
 *
 * See CjProductSummary's note — CJ's documented field names don't always
 * match what their API actually returns, so a short list of plausible
 * alternates is tried and a genuine mismatch is logged, not thrown.
 *
 * Confirmed against a real account (2026-10-04): `inventories` on a variant
 * can come back `null` even for a product whose search listing reports stock
 * (warehouseInventoryNum > 0) — CJ's own "which endpoint tells you real stock"
 * docs didn't hold up when the paths they named were tried directly (all
 * three returned "Interface not found"). So `null`/missing inventory data is
 * treated as *unknown*, not zero — a confirmed zero still blocks import, but
 * CJ simply not returning the data for this call doesn't.
 */
final class CjVariant
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $key,
        public readonly float $sellPrice,
        public readonly ?int $totalInventory,
        public readonly ?string $warehouseCountry,
        public readonly ?string $imageUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Prefer a warehouse that actually has stock; fall back to the first reported.
        $inventories = $data['inventories'] ?? [];
        $inventory = null;

        foreach ($inventories as $candidate) {
            if (($candidate['totalInventory'] ?? 0) > 0) {
                $inventory = $candidate;

                break;
            }
        }

        $inventory ??= $inventories[0] ?? null;

        $id = $data['vid'] ?? $data['variantId'] ?? $data['id'] ?? null;

        if ($id === null) {
            Log::warning('CJ variant had no recognizable vid field', ['data' => $data]);
        }

        return new self(
            id: (string) ($id ?? ''),
            name: $data['variantNameEn'] ?? $data['variantName'] ?? '',
            key: $data['variantKey'] ?? '',
            sellPrice: (float) ($data['variantSellPrice'] ?? $data['sellPrice'] ?? 0),
            totalInventory: isset($inventory['totalInventory']) ? (int) $inventory['totalInventory'] : null,
            warehouseCountry: $inventory['countryCode'] ?? null,
            imageUrl: $data['variantImage'] ?? null,
        );
    }

    /**
     * True when CJ confirms stock, or when CJ simply didn't report inventory
     * for this call — only an explicit zero counts as "not importable."
     */
    public function inStock(): bool
    {
        return $this->totalInventory === null || $this->totalInventory > 0;
    }

    public function stockKnown(): bool
    {
        return $this->totalInventory !== null;
    }
}

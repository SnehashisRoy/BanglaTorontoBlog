<?php

namespace App\Dto;

/**
 * One SKU variant of a CJ product (e.g. a specific color/size combination),
 * from the "variants" array of product/query.
 */
final class CjVariant
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $key,
        public readonly float $sellPrice,
        public readonly int $totalInventory,
        public readonly ?string $warehouseCountry,
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

        return new self(
            id: (string) $data['vid'],
            name: $data['variantNameEn'] ?? '',
            key: $data['variantKey'] ?? '',
            sellPrice: (float) ($data['variantSellPrice'] ?? 0),
            totalInventory: (int) ($inventory['totalInventory'] ?? 0),
            warehouseCountry: $inventory['countryCode'] ?? null,
        );
    }

    public function inStock(): bool
    {
        return $this->totalInventory > 0;
    }
}

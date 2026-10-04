<?php

namespace App\Dto;

/**
 * One row from CJ's product/listV2 search results.
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
        return new self(
            id: (string) $data['id'],
            name: $data['nameEn'] ?? '',
            imageUrl: $data['bigImage'] ?? null,
            sellPrice: (float) ($data['sellPrice'] ?? 0),
            categoryId: $data['categoryId'] ?? null,
            warehouseInventoryNum: (int) ($data['warehouseInventoryNum'] ?? 0),
        );
    }
}

<?php

namespace App\Dto;

/**
 * Full detail for one CJ product, from product/query, including its variants.
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
        return new self(
            pid: (string) $data['pid'],
            name: $data['productNameEn'] ?? '',
            imageUrl: $data['bigImage'] ?? null,
            description: $data['description'] ?? null,
            sellPrice: (float) ($data['sellPrice'] ?? 0),
            variants: array_map(
                fn (array $variant) => CjVariant::fromArray($variant),
                $data['variants'] ?? [],
            ),
        );
    }
}

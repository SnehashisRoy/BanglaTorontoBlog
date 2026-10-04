<?php

namespace App\Dto;

/**
 * A page of product/listV2 results, with CJ's own pagination metadata so the
 * UI can offer Next/Previous instead of silently showing only page one.
 */
final class CjSearchResult
{
    /**
     * @param  array<CjProductSummary>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $page,
        public readonly int $totalPages,
        public readonly int $totalRecords,
    ) {}

    /**
     * @param  array<string, mixed>  $data  the "data" object from CJ's response envelope
     * @param  array<CjProductSummary>  $items
     */
    public static function fromArray(array $data, array $items, int $requestedPage): self
    {
        return new self(
            items: $items,
            page: (int) ($data['pageNumber'] ?? $requestedPage),
            totalPages: (int) ($data['totalPages'] ?? 1),
            totalRecords: (int) ($data['totalRecords'] ?? count($items)),
        );
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->totalPages;
    }
}

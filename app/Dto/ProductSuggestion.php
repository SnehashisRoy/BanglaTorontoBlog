<?php

namespace App\Dto;

/**
 * AI-generated product title + description suggested from an uploaded photo.
 */
final class ProductSuggestion
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
    ) {}
}

<?php

namespace App\Repositories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProductCategoryRepository
{
    public function allWithProductCounts(): Collection
    {
        return ProductCategory::withCount('products')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): ProductCategory
    {
        return ProductCategory::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['slug'] ?? $data['name']),
        ]);
    }

    public function update(ProductCategory $category, array $data): ProductCategory
    {
        $category->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['slug'] ?? $data['name'], $category->id),
        ]);

        return $category;
    }

    public function delete(ProductCategory $category): void
    {
        $category->delete();
    }

    protected function uniqueSlug(string $source, ?int $ignoreCategoryId = null): string
    {
        $base = Str::slug($source);
        $slug = $base;
        $i = 1;

        $exists = function (string $candidate) use ($ignoreCategoryId) {
            return ProductCategory::where('slug', $candidate)
                ->when($ignoreCategoryId, fn ($q) => $q->where('id', '!=', $ignoreCategoryId))
                ->exists();
        };

        while ($exists($slug)) {
            $slug = "{$base}-".++$i;
        }

        return $slug;
    }
}

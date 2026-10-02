<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductRepository
{
    public function publishedFeed(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Product::with(['vendor', 'category', 'images'])
            ->published()
            ->whereHas('vendor', fn ($q) => $q->where('status', Vendor::STATUS_APPROVED));

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category']));
        }

        if (! empty($filters['vendor'])) {
            $query->whereHas('vendor', fn ($q) => $q->where('slug', $filters['vendor']));
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function findPublished(string $vendorSlug, string $productSlug): Product
    {
        return Product::with(['vendor', 'category', 'images'])
            ->published()
            ->whereHas('vendor', fn ($q) => $q->where('status', Vendor::STATUS_APPROVED)->where('slug', $vendorSlug))
            ->where('slug', $productSlug)
            ->firstOrFail();
    }

    public function forVendor(Vendor $vendor, int $perPage = 15): LengthAwarePaginator
    {
        return Product::with(['category', 'images'])
            ->where('vendor_id', $vendor->id)
            ->latest()
            ->paginate($perPage);
    }

    public function createForVendor(Vendor $vendor, array $data): Product
    {
        return Product::create([
            'vendor_id' => $vendor->id,
            'product_category_id' => $data['product_category_id'] ?? null,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($vendor, $data['slug'] ?? $data['name']),
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? null,
            'status' => $data['status'],
        ]);
    }

    public function updateForVendor(Product $product, array $data): Product
    {
        $product->update([
            'product_category_id' => $data['product_category_id'] ?? null,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($product->vendor, $data['slug'] ?? $data['name'], $product->id),
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? null,
            'status' => $data['status'],
        ]);

        return $product;
    }

    public function deleteForVendor(Product $product): void
    {
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $product->delete();
    }

    /**
     * Store newly uploaded images and remove any requested by id.
     *
     * @param  array<UploadedFile>  $uploadedFiles
     * @param  array<int>  $deleteIds
     */
    public function syncImages(Product $product, array $uploadedFiles, array $deleteIds = []): void
    {
        if (! empty($deleteIds)) {
            $images = $product->images()->whereIn('id', $deleteIds)->get();

            foreach ($images as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }
        }

        $nextOrder = (int) $product->images()->max('sort_order') + 1;

        foreach ($uploadedFiles as $file) {
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'sort_order' => $nextOrder++,
            ]);
        }
    }

    protected function uniqueSlug(Vendor $vendor, string $source, ?int $ignoreProductId = null): string
    {
        $base = Str::slug($source);
        $slug = $base;
        $i = 1;

        $exists = function (string $candidate) use ($vendor, $ignoreProductId) {
            return Product::where('vendor_id', $vendor->id)
                ->where('slug', $candidate)
                ->when($ignoreProductId, fn ($q) => $q->where('id', '!=', $ignoreProductId))
                ->exists();
        };

        while ($exists($slug)) {
            $slug = "{$base}-".++$i;
        }

        return $slug;
    }
}

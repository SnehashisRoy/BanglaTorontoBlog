<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class VendorRepository
{
    public function activeWithProductCounts(): Collection
    {
        return Vendor::active()
            ->withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('name')
            ->get();
    }

    public function findActiveBySlug(string $slug): Vendor
    {
        return Vendor::active()->where('slug', $slug)->firstOrFail();
    }

    /**
     * All vendors for the admin list — pending requests first, then newest.
     */
    public function allWithProductCounts(): Collection
    {
        return Vendor::with('user')
            ->withCount('products')
            ->orderByRaw('status = ? desc', [Vendor::STATUS_PENDING])
            ->latest()
            ->get();
    }

    public function createForUser(User $user, array $data): Vendor
    {
        return Vendor::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'logo_path' => $data['logo_path'] ?? null,
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'website' => $data['website'] ?? null,
            'status' => Vendor::STATUS_PENDING,
        ]);
    }

    public function setStatus(Vendor $vendor, string $status): Vendor
    {
        $vendor->update(['status' => $status]);

        return $vendor;
    }

    public function setCjDropshippingEnabled(Vendor $vendor, bool $enabled): Vendor
    {
        $vendor->update(['cj_dropshipping_enabled' => $enabled]);

        return $vendor;
    }

    public function setCjMarkupPercent(Vendor $vendor, float $percent): Vendor
    {
        $vendor->update(['cj_markup_percent' => $percent]);

        return $vendor;
    }

    public function updateForUser(Vendor $vendor, array $data): Vendor
    {
        $vendor->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'logo_path' => array_key_exists('logo_path', $data) ? $data['logo_path'] : $vendor->logo_path,
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'website' => $data['website'] ?? null,
        ]);

        return $vendor;
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Vendor::where('slug', $slug)->exists()) {
            $slug = "{$base}-".++$i;
        }

        return $slug;
    }
}

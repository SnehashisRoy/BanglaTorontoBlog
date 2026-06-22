<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CompanyRepository
{
    public function categories(): Collection
    {
        return Company::select('slug')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('slug')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => (object) [
                'slug'  => $row->slug,
                'label' => Company::CATEGORY_LABELS[$row->slug] ?? ucwords(str_replace('-', ' ', $row->slug)),
                'icon'  => Company::CATEGORY_ICONS[$row->slug] ?? '🏢',
                'count' => $row->count,
            ]);
    }

    public function byCategory(string $slug): EloquentCollection
    {
        return Company::where('slug', $slug)
            ->orderBy('company')
            ->get();
    }

    public function find(string $slug, string $companySlug): Company
    {
        return Company::where('slug', $slug)
            ->where('company_slug', $companySlug)
            ->firstOrFail();
    }
}

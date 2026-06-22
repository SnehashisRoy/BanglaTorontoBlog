<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Repositories\CompanyRepository;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(private readonly CompanyRepository $companies) {}

    public function index(): View
    {
        $categories = $this->companies->categories();

        return view('companies.index', compact('categories'));
    }

    public function category(string $slug): View
    {
        $companies = $this->companies->byCategory($slug);

        abort_if($companies->isEmpty(), 404);

        $label = Company::CATEGORY_LABELS[$slug] ?? ucwords(str_replace('-', ' ', $slug));
        $icon  = Company::CATEGORY_ICONS[$slug] ?? '🏢';

        return view('companies.category', compact('companies', 'slug', 'label', 'icon'));
    }

    public function show(string $slug, string $companySlug): View
    {
        $company       = $this->companies->find($slug, $companySlug);
        $categoryLabel = Company::CATEGORY_LABELS[$slug] ?? ucwords(str_replace('-', ' ', $slug));

        return view('companies.show', compact('company', 'categoryLabel'));
    }
}

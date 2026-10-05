<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use App\Repositories\ProductCategoryRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function __construct(private readonly ProductCategoryRepository $categories) {}

    public function index(): View
    {
        $categories = $this->categories->allWithProductCounts();

        return view('admin.product-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.product-categories.create');
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());

        return redirect()->route('admin.product-categories.index')->with('success', 'Category created.');
    }

    public function edit(ProductCategory $product_category): View
    {
        return view('admin.product-categories.edit', ['category' => $product_category]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $product_category): RedirectResponse
    {
        $this->categories->update($product_category, $request->validated());

        return redirect()->route('admin.product-categories.index')->with('success', 'Category updated.');
    }

    public function destroy(ProductCategory $product_category): RedirectResponse
    {
        $this->categories->delete($product_category);

        return redirect()->route('admin.product-categories.index')->with('success', 'Category deleted.');
    }
}

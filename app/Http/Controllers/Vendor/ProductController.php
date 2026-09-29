<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ProductRepository $products) {}

    public function index(Request $request): View
    {
        $products = $this->products->forVendor($request->user()->vendor);

        return view('vendor.products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = ProductCategory::orderBy('name')->get();

        return view('vendor.products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $product = $this->products->createForVendor($request->user()->vendor, $data);

        $this->products->syncImages($product, $request->file('images', []));

        return redirect()->route('vendor.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load('images');
        $categories = ProductCategory::orderBy('name')->get();

        return view('vendor.products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        $this->products->updateForVendor($product, $data);
        $this->products->syncImages(
            $product,
            $request->file('images', []),
            $data['delete_image_ids'] ?? [],
        );

        return redirect()->route('vendor.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->products->deleteForVendor($product);

        return redirect()->route('vendor.products.index')->with('success', 'Product deleted.');
    }
}

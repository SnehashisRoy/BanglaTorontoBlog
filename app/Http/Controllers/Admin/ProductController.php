<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductRepository $products) {}

    public function index(Request $request): View
    {
        $products = $this->products->allForAdmin([
            'search' => $request->query('search'),
            'featured' => $request->boolean('featured'),
        ]);

        return view('admin.products.index', compact('products'));
    }

    public function feature(Product $product): RedirectResponse
    {
        $this->products->setFeatured($product, true);

        return redirect()->route('admin.products.index')->with('success', "{$product->name} is now featured on the home page.");
    }

    public function unfeature(Product $product): RedirectResponse
    {
        $this->products->setFeatured($product, false);

        return redirect()->route('admin.products.index')->with('success', "{$product->name} removed from featured products.");
    }
}

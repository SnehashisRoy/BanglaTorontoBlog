<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private readonly ProductRepository $products) {}

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $isSearching = filled($search) || filled($category);

        $categories = ProductCategory::orderBy('name')->get();
        $featuredProducts = $this->products->featuredFeed();

        $products = $isSearching
            ? $this->products->publishedFeed(['category' => $category, 'search' => $search])
            : null;

        return view('home', compact('products', 'categories', 'featuredProducts', 'isSearching'));
    }
}

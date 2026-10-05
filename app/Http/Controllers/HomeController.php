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
        $products = $this->products->publishedFeed([
            'category' => $request->query('category'),
            'search' => $request->query('search'),
        ]);

        $categories = ProductCategory::orderBy('name')->get();
        $featuredProducts = $this->products->featuredFeed();

        return view('home', compact('products', 'categories', 'featuredProducts'));
    }
}

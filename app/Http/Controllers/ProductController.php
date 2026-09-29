<?php

namespace App\Http\Controllers;

use App\Repositories\ProductRepository;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductRepository $products) {}

    public function show(string $vendor, string $product): View
    {
        $product = $this->products->findPublished($vendor, $product);

        return view('products.show', compact('product'));
    }
}

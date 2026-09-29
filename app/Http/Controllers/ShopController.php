<?php

namespace App\Http\Controllers;

use App\Repositories\ProductRepository;
use App\Repositories\VendorRepository;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __construct(
        private readonly VendorRepository $vendors,
        private readonly ProductRepository $products,
    ) {}

    public function index(): View
    {
        $vendors = $this->vendors->activeWithProductCounts();

        return view('shops.index', compact('vendors'));
    }

    public function show(string $vendor): View
    {
        $vendor = $this->vendors->findActiveBySlug($vendor);
        $products = $this->products->publishedFeed(['vendor' => $vendor->slug]);

        return view('shops.show', compact('vendor', 'products'));
    }
}

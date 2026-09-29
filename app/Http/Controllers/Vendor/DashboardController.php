<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = $request->user()->vendor;

        $stats = [
            'published' => $vendor->products()->where('status', 'published')->count(),
            'draft' => $vendor->products()->where('status', 'draft')->count(),
        ];

        return view('vendor.dashboard', compact('vendor', 'stats'));
    }
}

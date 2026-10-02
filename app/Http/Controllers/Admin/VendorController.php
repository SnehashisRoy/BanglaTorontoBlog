<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Repositories\VendorRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(private readonly VendorRepository $vendors) {}

    public function index(): View
    {
        $vendors = $this->vendors->allWithProductCounts();

        return view('admin.vendors.index', compact('vendors'));
    }

    public function approve(Vendor $vendor): RedirectResponse
    {
        $this->vendors->setStatus($vendor, Vendor::STATUS_APPROVED);

        return redirect()->route('admin.vendors.index')->with('success', "{$vendor->name} approved — their shop is now live.");
    }

    public function markPending(Vendor $vendor): RedirectResponse
    {
        $this->vendors->setStatus($vendor, Vendor::STATUS_PENDING);

        return redirect()->route('admin.vendors.index')->with('success', "{$vendor->name} moved back to pending — their shop is hidden until re-approved.");
    }
}

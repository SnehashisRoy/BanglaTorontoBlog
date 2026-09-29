<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVendorRequest;
use App\Repositories\VendorRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __construct(private readonly VendorRepository $vendors) {}

    public function edit(Request $request): View
    {
        $vendor = $request->user()->vendor;

        return view('vendor.shop', compact('vendor'));
    }

    public function update(UpdateVendorRequest $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        $data = $request->validated();

        if ($request->file('logo')) {
            if ($vendor->logo_path) {
                Storage::disk('public')->delete($vendor->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('vendors/logos', 'public');
        } elseif ($request->boolean('remove_logo') && $vendor->logo_path) {
            Storage::disk('public')->delete($vendor->logo_path);
            $data['logo_path'] = null;
        }

        $this->vendors->updateForUser($vendor, $data);

        return redirect()->route('vendor.shop.edit')->with('success', 'Shop updated.');
    }
}

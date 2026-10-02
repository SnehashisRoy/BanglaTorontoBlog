<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorRequest;
use App\Repositories\VendorRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(private readonly VendorRepository $vendors) {}

    public function create(): View|RedirectResponse
    {
        if (auth()->user()->vendor) {
            return redirect()->route('vendor.dashboard');
        }

        return view('vendor.register');
    }

    public function store(StoreVendorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['logo_path'] = $request->file('logo')?->store('vendors/logos', 'public');

        $this->vendors->createForUser($request->user(), $data);

        return redirect()->route('vendor.dashboard')
            ->with('success', 'Your shop request has been submitted! An admin will review it shortly — you can set up your products in the meantime.');
    }
}

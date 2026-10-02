<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorRequest;
use App\Repositories\VendorRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(private readonly VendorRepository $vendors) {}

    /**
     * Entry point for "I want to sell" links/buttons aimed at guests.
     * Remembers the vendor-registration intent across the login/register
     * detour so a brand-new (or logged-out) visitor lands back here —
     * instead of the generic dashboard — once they've authenticated.
     */
    public function intent(Request $request, string $via): RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('vendor.register');
        }

        $request->session()->put('url.intended', route('vendor.register'));

        return redirect()->route($via);
    }

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

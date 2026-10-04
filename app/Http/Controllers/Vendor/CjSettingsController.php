<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConnectCjRequest;
use App\Http\Requests\UpdateCjMarkupRequest;
use App\Repositories\CjCredentialRepository;
use App\Repositories\VendorRepository;
use App\Services\CjApiException;
use App\Services\CjDropshippingClient;
use App\Services\CjProductSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CjSettingsController extends Controller
{
    public function __construct(
        private readonly CjDropshippingClient $client,
        private readonly CjCredentialRepository $credentials,
        private readonly VendorRepository $vendors,
        private readonly CjProductSyncService $sync,
    ) {}

    public function edit(Request $request): View
    {
        $vendor = $request->user()->vendor;
        abort_unless($vendor->cj_dropshipping_enabled, 403);

        $vendor->load('cjCredential');

        return view('vendor.cj.edit', compact('vendor'));
    }

    public function connect(ConnectCjRequest $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        $data = $request->validated();

        try {
            $pair = $this->client->connect($data['api_key']);
        } catch (CjApiException $e) {
            report($e);

            return back()->withErrors([
                'api_key' => 'Could not connect to CJ Dropshipping. Double-check your API key and try again.',
            ]);
        }

        $this->credentials->store($vendor, $pair);

        return redirect()->route('vendor.cj.edit')->with('success', 'Connected to CJ Dropshipping.');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        abort_unless($vendor->cj_dropshipping_enabled, 403);

        $this->credentials->forget($vendor);

        return redirect()->route('vendor.cj.edit')->with('success', 'Disconnected from CJ Dropshipping.');
    }

    public function updateMarkup(UpdateCjMarkupRequest $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        $data = $request->validated();

        $this->vendors->setCjMarkupPercent($vendor, (float) $data['cj_markup_percent']);
        $this->sync->repriceForVendor($vendor);

        return redirect()->route('vendor.cj.edit')->with('success', 'Markup updated — existing CJ products repriced.');
    }
}

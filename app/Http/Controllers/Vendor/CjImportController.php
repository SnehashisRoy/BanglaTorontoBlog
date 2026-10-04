<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\CjApiException;
use App\Services\CjCurrencyConverter;
use App\Services\CjDropshippingClient;
use App\Services\ProductImportFromCjService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CjImportController extends Controller
{
    public function __construct(
        private readonly CjDropshippingClient $client,
        private readonly ProductImportFromCjService $importer,
        private readonly CjCurrencyConverter $currency,
    ) {}

    public function search(Request $request): View
    {
        $vendor = $request->user()->vendor;
        abort_unless($vendor->hasConnectedCj(), 403);

        $results = null;
        $error = null;

        if ($request->filled('keyword')) {
            try {
                $results = $this->client->search(
                    $vendor->cjCredential,
                    ['keyWord' => $request->string('keyword')->toString()],
                    page: max(1, $request->integer('page', 1)),
                );
            } catch (CjApiException $e) {
                report($e);
                $error = 'Could not search CJ Dropshipping right now. Please try again shortly.';
            }
        }

        return view('vendor.cj.import.search', [
            ...compact('vendor', 'results', 'error'),
            'currency' => $this->currency,
        ]);
    }

    public function show(Request $request, string $pid): View
    {
        $vendor = $request->user()->vendor;
        abort_unless($vendor->hasConnectedCj(), 403);

        try {
            $product = $this->client->productDetail($vendor->cjCredential, $pid);
        } catch (CjApiException $e) {
            report($e);
            abort(502, 'Could not load this product from CJ Dropshipping right now.');
        }

        return view('vendor.cj.import.show', [
            ...compact('vendor', 'product'),
            'currency' => $this->currency,
        ]);
    }

    public function store(Request $request, string $pid): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        abort_unless($vendor->hasConnectedCj(), 403);

        $data = $request->validate([
            'variant_ids' => ['required', 'array', 'min:1'],
            'variant_ids.*' => ['string'],
        ]);

        try {
            $product = $this->client->productDetail($vendor->cjCredential, $pid);
        } catch (CjApiException $e) {
            report($e);

            return back()->withErrors(['variant_ids' => 'Could not import — CJ Dropshipping did not respond. Please try again.']);
        }

        $imported = 0;

        foreach ($product->variants as $variant) {
            if (in_array($variant->id, $data['variant_ids'], true) && $variant->inStock()) {
                $this->importer->importVariant($vendor, $product, $variant);
                $imported++;
            }
        }

        return redirect()->route('vendor.products.index')
            ->with('success', "Imported {$imported} product(s) from CJ as drafts — review and publish when ready.");
    }
}

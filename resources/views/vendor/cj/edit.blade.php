@extends('layouts.vendor')

@section('title', 'CJ Dropshipping')

@section('content')

    <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-8 max-w-2xl">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">CJ Dropshipping</h1>
        <p class="text-sm text-gray-500 mb-6">
            Connect your own CJ Dropshipping account to import products straight into your shop.
        </p>

        @if($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if($vendor->cjCredential)
            <div class="mb-6 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-4 py-3">
                <p class="text-sm text-green-800">
                    <span class="font-semibold">✓ Connected</span>
                    · since {{ $vendor->cjCredential->connected_at->format('M d, Y') }}
                </p>
                <form action="{{ route('vendor.cj.disconnect') }}" method="POST" onsubmit="return confirm('Disconnect your CJ account? You can reconnect any time.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700">
                        Disconnect
                    </button>
                </form>
            </div>
        @else
            <form action="{{ route('vendor.cj.connect') }}" method="POST" class="space-y-4 mb-8">
                @csrf

                <div>
                    <label for="api_key" class="block text-sm font-medium text-gray-700 mb-1">CJ API Key</label>
                    <input type="text" id="api_key" name="api_key" value="{{ old('api_key') }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                    <p class="mt-1 text-xs text-gray-400">
                        Generate this from your own CJ Dropshipping account — we never ask for your CJ password,
                        and this key alone is never used to place or modify any order.
                    </p>
                </div>

                <button type="submit"
                        class="rounded-lg bg-[#27ae60] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors">
                    Connect
                </button>
            </form>
        @endif

        <hr class="border-gray-200 mb-6">

        <form action="{{ route('vendor.cj.markup') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="cj_markup_percent" class="block text-sm font-medium text-gray-700 mb-1">
                    Markup on CJ products
                </label>
                <div class="flex items-center gap-2 max-w-[160px]">
                    <input type="number" id="cj_markup_percent" name="cj_markup_percent" step="0.01" min="0" max="500"
                           value="{{ old('cj_markup_percent', $vendor->cj_markup_percent) }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                    <span class="text-sm text-gray-500">%</span>
                </div>
                <p class="mt-1 text-xs text-gray-400">
                    Your selling price = CJ's cost × (1 + markup). Applies to every product you import, and
                    repricing existing ones happens immediately when you save — not just on the next daily sync.
                </p>
            </div>

            <button type="submit"
                    class="rounded-lg bg-[#27ae60] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors">
                Save Markup
            </button>
        </form>
    </div>

@endsection

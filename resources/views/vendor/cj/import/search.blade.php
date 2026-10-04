@extends('layouts.vendor')

@section('title', 'Import from CJ')

@section('content')

    <div class="mb-6">
        <a href="{{ route('vendor.products.index') }}" class="text-sm text-[#27ae60] hover:text-[#1a7a44]">&larr; Back to products</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-8 max-w-3xl">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">Import from CJ Dropshipping</h1>

        @if($error)
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $error }}
            </div>
        @endif

        <form action="{{ route('vendor.cj.import.search') }}" method="GET" class="flex flex-col sm:flex-row gap-3 mb-8">
            <input type="text" name="keyword" value="{{ request('keyword') }}"
                   placeholder="Search CJ's catalog…" required
                   class="flex-1 rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
            <button type="submit"
                    class="rounded-lg bg-[#27ae60] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors">
                Search
            </button>
        </form>

        @if(request()->filled('keyword') && $results)
            @if(empty($results->items))
                <p class="text-gray-500">No results for "{{ request('keyword') }}".</p>
            @else
                <p class="text-xs text-gray-500 mb-3">
                    {{ number_format($results->totalRecords) }} result(s) on CJ for "{{ request('keyword') }}"
                    · page {{ $results->page }} of {{ number_format($results->totalPages) }}
                </p>

                <div class="space-y-3">
                    @foreach($results->items as $result)
                        @php
                            $markup = (float) ($vendor->cj_markup_percent ?? 0);
                            $yourPrice = $currency->sellingPrice($result->sellPrice, $markup);
                        @endphp
                        <a href="{{ route('vendor.cj.import.show', $result->id) }}"
                           class="flex items-center gap-4 rounded-xl border border-gray-200 p-3 sm:p-4 hover:border-gray-300 hover:shadow-sm transition">
                            <div class="shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-lg bg-gray-100 overflow-hidden">
                                @if($result->imageUrl)
                                    <img src="{{ $result->imageUrl }}" alt="{{ $result->name }}" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-900 leading-snug truncate">{{ $result->name }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    CJ cost: ${{ number_format($result->sellPrice, 2) }} USD (excl. shipping)
                                    @if($result->warehouseInventoryNum > 0)
                                        · {{ $result->warehouseInventoryNum }} in stock
                                    @endif
                                </p>
                                <p class="text-sm font-medium mt-1" style="color:#27ae60;">
                                    Your price: CA${{ number_format($yourPrice, 2) }} ({{ $markup }}% markup)
                                </p>
                            </div>
                            <span class="shrink-0 text-sm text-gray-400">View variants →</span>
                        </a>
                    @endforeach
                </div>

                @if($results->hasPrevious() || $results->hasNext())
                    <div class="mt-6 flex items-center justify-between">
                        @if($results->hasPrevious())
                            <a href="{{ route('vendor.cj.import.search', ['keyword' => request('keyword'), 'page' => $results->page - 1]) }}"
                               class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                ← Previous
                            </a>
                        @else
                            <span></span>
                        @endif

                        @if($results->hasNext())
                            <a href="{{ route('vendor.cj.import.search', ['keyword' => request('keyword'), 'page' => $results->page + 1]) }}"
                               class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                Next →
                            </a>
                        @endif
                    </div>
                @endif
            @endif
        @endif
    </div>

@endsection

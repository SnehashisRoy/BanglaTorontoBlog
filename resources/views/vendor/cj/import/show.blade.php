@extends('layouts.vendor')

@section('title', $product->name)

@section('content')

    <div class="mb-6">
        <a href="{{ route('vendor.cj.import.search') }}" class="text-sm text-[#27ae60] hover:text-[#1a7a44]">&larr; Back to search</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-8 max-w-2xl">
        <div class="flex items-start gap-4 mb-6">
            <div class="shrink-0 w-20 h-20 rounded-lg bg-gray-100 overflow-hidden">
                @if($product->imageUrl)
                    <img src="{{ $product->imageUrl }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                @endif
            </div>
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 leading-snug">{{ $product->name }}</h1>
                <p class="text-xs text-gray-500 mt-1">CJ product ID: {{ $product->pid }}</p>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if(empty($product->variants))
            <p class="text-gray-500">CJ didn't report any variants for this product.</p>
        @else
            <form action="{{ route('vendor.cj.import.store', $product->pid) }}" method="POST" class="space-y-4">
                @csrf

                <p class="text-sm font-medium text-gray-700">Choose which variant(s) to import — each becomes its own product in your shop:</p>

                <div class="space-y-2">
                    @foreach($product->variants as $variant)
                        @php
                            $markup = (float) ($vendor->cj_markup_percent ?? 0);
                            $yourPrice = round($variant->sellPrice * (1 + $markup / 100), 2);
                        @endphp
                        <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 {{ $variant->inStock() ? 'cursor-pointer hover:border-gray-300' : 'opacity-50' }}">
                            <input type="checkbox" name="variant_ids[]" value="{{ $variant->id }}"
                                   {{ $variant->inStock() ? '' : 'disabled' }}
                                   class="rounded border-gray-300 text-[#27ae60] focus:ring-[#27ae60]">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $variant->key ?: $variant->name }}</p>
                                <p class="text-xs text-gray-500">
                                    CJ cost: ${{ number_format($variant->sellPrice, 2) }}
                                    · Your price: ${{ number_format($yourPrice, 2) }}
                                    @if($variant->warehouseCountry)
                                        · Ships from: {{ $variant->warehouseCountry }}
                                    @endif
                                </p>
                            </div>
                            <span class="text-xs font-medium {{ $variant->inStock() ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $variant->inStock() ? 'In stock' : 'Out of stock' }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <button type="submit"
                        class="rounded-lg bg-[#27ae60] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors">
                    Import Selected as Separate Products
                </button>
            </form>
        @endif
    </div>

@endsection

@extends('layouts.public')

@section('title', $vendor->name)

@section('content')

    <div class="mb-6">
        <a href="{{ route('shops.index') }}" class="text-sm text-[#27ae60] hover:text-[#1a7a44]">
            &larr; {{ __('All shops') }}
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-8 mb-8">
        <div class="flex items-start gap-4">
            <div class="shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center text-2xl">
                @if($vendor->logoUrl())
                    <img src="{{ $vendor->logoUrl() }}" alt="{{ $vendor->name }}" class="w-full h-full object-cover">
                @else
                    🏪
                @endif
            </div>
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $vendor->name }}</h1>
                @if($vendor->city)
                    <p class="text-sm text-gray-500 mt-0.5">{{ $vendor->city }}</p>
                @endif
                @if($vendor->description)
                    <p class="text-sm text-gray-600 mt-3 leading-relaxed">{{ $vendor->description }}</p>
                @endif
            </div>
        </div>
    </div>

    @if($products->isEmpty())
        <p class="text-gray-500">{{ __('This shop has no products yet.') }}</p>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-5">
            @foreach($products as $product)
                <a href="{{ route('shops.products.show', ['vendor' => $vendor->slug, 'product' => $product->slug]) }}"
                   class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:border-gray-300 hover:shadow-sm transition">
                    <div class="aspect-square bg-gray-100 overflow-hidden">
                        @if($product->primaryImageUrl())
                            <img src="{{ $product->primaryImageUrl() }}" alt="{{ $product->name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-3xl text-gray-300">🛍️</div>
                        @endif
                    </div>
                    <div class="p-3 sm:p-4">
                        <h2 class="text-sm sm:text-base font-semibold text-gray-900 leading-snug line-clamp-2 group-hover:text-[#27ae60] transition-colors">
                            {{ $product->name }}
                        </h2>
                        <p class="mt-1.5 text-sm font-medium" style="color:#27ae60;">
                            {{ $product->price !== null ? '$'.number_format((float) $product->price, 2) : __('Contact for price') }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">{{ $products->links() }}</div>
    @endif

@endsection

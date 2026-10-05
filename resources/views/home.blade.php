@extends('layouts.public')

@section('title', __('Shop'))

@section('content')

    {{-- Hero banner --}}
    <div class="relative rounded-2xl overflow-hidden mb-8"
         style="background: linear-gradient(135deg, #1a1a1a 0%, #27ae60 100%);">
        <div class="absolute inset-0 opacity-10"
             style="background-image: repeating-linear-gradient(45deg, #2ecc71 0, #2ecc71 1px, transparent 0, transparent 50%); background-size: 20px 20px;"></div>
        <div class="relative px-6 py-10 sm:px-10 sm:py-14 text-white">
            <h1 class="text-2xl sm:text-4xl font-bold leading-tight mb-3">
                {{ __('Shop from local vendors') }}
            </h1>
            <p class="text-sm sm:text-base opacity-80 max-w-lg">
                {{ __('Browse products from Bengali-owned shops across Toronto and the GTA.') }}
            </p>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a href="{{ route('shops.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-lg transition-transform hover:-translate-y-0.5"
                   style="background: #2ecc71; box-shadow: 0 8px 24px rgba(46,204,113,0.35);">
                    <span class="text-base">🏪</span>
                    {{ __('Browse All Shops') }}
                    <span aria-hidden="true">&rarr;</span>
                </a>
                <button type="button"
                        onclick="document.getElementById('sell-with-us-modal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 rounded-lg border border-white/50 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-white/10">
                    <span class="text-base">✨</span>
                    {{ __('Sell With Us') }}
                </button>
            </div>
        </div>
    </div>

    {{-- "Sell With Us" modal --}}
    <div id="sell-with-us-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
         onclick="if (event.target === this) this.classList.add('hidden')">
        <div class="absolute inset-0 bg-black/50"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 sm:p-8">
            <button type="button"
                    onclick="document.getElementById('sell-with-us-modal').classList.add('hidden')"
                    aria-label="{{ __('Close') }}"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <h2 class="text-xl font-bold text-gray-900 mb-2">{{ __('Open your shop in minutes') }}</h2>
            <p class="text-sm text-gray-500 mb-6">
                {{ __("It's easier than you think — no design skills or writing needed.") }}
            </p>

            <ol class="space-y-4 mb-6">
                <li class="flex items-start gap-3">
                    <span class="shrink-0 flex items-center justify-center h-8 w-8 rounded-full text-sm font-bold text-white" style="background:#27ae60;">1</span>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ __('Upload a photo') }}</p>
                        <p class="text-sm text-gray-500">{{ __('Just a picture of your product — that\'s all you need to start.') }}</p>
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="shrink-0 flex items-center justify-center h-8 w-8 rounded-full text-sm font-bold text-white" style="background:#27ae60;">2</span>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ __('We write the title & description') }}</p>
                        <p class="text-sm text-gray-500">{{ __('Our AI suggests a title and description from your photo — edit if you like, or just go with it.') }}</p>
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="shrink-0 flex items-center justify-center h-8 w-8 rounded-full text-sm font-bold text-white" style="background:#27ae60;">3</span>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ __("You're live") }}</p>
                        <p class="text-sm text-gray-500">{{ __('Set a price and you\'re ready to go — no coding, no setup fees.') }}</p>
                    </div>
                </li>
            </ol>

            @auth
                @if(auth()->user()->vendor)
                    <a href="{{ route('vendor.dashboard') }}"
                       class="block w-full text-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition-transform hover:-translate-y-0.5"
                       style="background:#27ae60;">
                        {{ __('Go to My Dashboard') }}
                    </a>
                @else
                    <a href="{{ route('vendor.register') }}"
                       class="block w-full text-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition-transform hover:-translate-y-0.5"
                       style="background:#27ae60;">
                        {{ __('Create Your Shop') }}
                    </a>
                @endif
            @else
                <a href="{{ route('vendor.intent', 'register') }}"
                   class="block w-full text-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition-transform hover:-translate-y-0.5"
                   style="background:#27ae60;">
                    {{ __('Get Started') }}
                </a>
            @endauth
        </div>
    </div>

    {{-- Filters --}}
    <form action="{{ route('home') }}" method="GET" class="mb-8 flex flex-col sm:flex-row gap-3">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="{{ __('Search products…') }}"
               class="flex-1 rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
        <select name="category" onchange="this.form.submit()"
                class="rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
            <option value="">{{ __('All Categories') }}</option>
            @foreach($categories as $category)
                <option value="{{ $category->slug }}" {{ request('category') === $category->slug ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        <button type="submit"
                class="rounded-lg bg-[#27ae60] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors">
            {{ __('Search') }}
        </button>
    </form>

    @if($isSearching)
        {{-- Search results --}}
        @if($products->isEmpty())
            <p class="text-gray-500">{{ __('No products found.') }}</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-5">
                @foreach($products as $product)
                    <a href="{{ route('shops.products.show', ['vendor' => $product->vendor->slug, 'product' => $product->slug]) }}"
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
                            <p class="text-xs text-gray-500 mb-1 truncate">{{ $product->vendor->name }}</p>
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
    @else
        {{-- Featured products (the normal home-page view) --}}
        @if($featuredProducts->isEmpty())
            <p class="text-gray-500">{{ __('No featured products yet — check back soon.') }}</p>
        @else
            <div class="mb-8">
                <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span style="color:#27ae60;">★</span> {{ __('Featured Products') }}
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-5">
                    @foreach($featuredProducts as $product)
                        <a href="{{ route('shops.products.show', ['vendor' => $product->vendor->slug, 'product' => $product->slug]) }}"
                           class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:border-gray-300 hover:shadow-sm transition">
                            <div class="aspect-square bg-gray-100 overflow-hidden relative">
                                <span class="absolute top-2 left-2 z-10 rounded-full bg-amber-400 text-white text-[10px] font-bold px-2 py-0.5 shadow">
                                    ★ {{ __('Featured') }}
                                </span>
                                @if($product->primaryImageUrl())
                                    <img src="{{ $product->primaryImageUrl() }}" alt="{{ $product->name }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-3xl text-gray-300">🛍️</div>
                                @endif
                            </div>
                            <div class="p-3 sm:p-4">
                                <p class="text-xs text-gray-500 mb-1 truncate">{{ $product->vendor->name }}</p>
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
            </div>
        @endif
    @endif

@endsection

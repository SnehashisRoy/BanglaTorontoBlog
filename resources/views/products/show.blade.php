@extends('layouts.public')

@section('title', $product->name)

@section('content')

    <div class="mb-6">
        <a href="{{ route('shops.show', $product->vendor->slug) }}" class="text-sm text-[#27ae60] hover:text-[#1a7a44]">
            &larr; {{ __('Back to :shop', ['shop' => $product->vendor->name]) }}
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="grid grid-cols-1 sm:grid-cols-2">

            {{-- Gallery --}}
            <div>
                @if($product->images->isNotEmpty())
                    <div class="aspect-square bg-gray-100">
                        <img id="main-image" src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}"
                             class="w-full h-full object-cover">
                    </div>
                    @if($product->images->count() > 1)
                        <div class="flex gap-2 p-3 overflow-x-auto">
                            @foreach($product->images as $image)
                                <button type="button" onclick="document.getElementById('main-image').src = this.dataset.src"
                                        data-src="{{ $image->url() }}"
                                        class="shrink-0 w-16 h-16 rounded-lg overflow-hidden border border-gray-200 hover:border-[#27ae60] transition-colors">
                                    <img src="{{ $image->url() }}" alt="" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="aspect-square bg-gray-100 flex items-center justify-center text-5xl text-gray-300">🛍️</div>
                @endif
            </div>

            {{-- Details --}}
            <div class="p-5 sm:p-8">
                @if($product->category)
                    <span class="inline-block rounded-full px-2.5 py-1 text-xs font-medium mb-3" style="background:#e8f8f0; color:#27ae60;">
                        {{ $product->category->name }}
                    </span>
                @endif

                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2 leading-tight">{{ $product->name }}</h1>

                <a href="{{ route('shops.show', $product->vendor->slug) }}" class="text-sm text-gray-500 hover:text-gray-700">
                    {{ __('Sold by') }} <span class="font-medium">{{ $product->vendor->name }}</span>
                </a>

                <p class="mt-4 text-2xl font-bold" style="color:#27ae60;">
                    {{ $product->price !== null ? '$'.number_format((float) $product->price, 2) : __('Contact for price') }}
                </p>

                @if($product->description)
                    <div class="mt-5 text-sm text-gray-600 leading-relaxed whitespace-pre-line">
                        {{ $product->description }}
                    </div>
                @endif

                {{-- Buy / Inquire — reveals vendor contact details --}}
                <div class="mt-6">
                    <button type="button" onclick="document.getElementById('contact-panel').classList.remove('hidden'); this.classList.add('hidden')"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-[#27ae60] px-5 py-3 text-sm font-semibold text-white hover:bg-[#1a7a44] transition-colors">
                        {{ __('Buy / Inquire') }}
                    </button>

                    <div id="contact-panel" class="hidden mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-2 text-sm">
                        <p class="font-semibold text-gray-900 mb-2">{{ __('Contact :shop', ['shop' => $product->vendor->name]) }}</p>

                        <p class="flex items-center gap-2 text-gray-700">
                            <span>📞</span>
                            <a href="tel:{{ $product->vendor->phone }}" class="hover:text-[#27ae60]">{{ $product->vendor->phone }}</a>
                        </p>

                        @if($product->vendor->whatsapp)
                            <p class="flex items-center gap-2 text-gray-700">
                                <span>💬</span>
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $product->vendor->whatsapp) }}"
                                   target="_blank" rel="noopener" class="hover:text-[#27ae60]">
                                    {{ __('WhatsApp') }}: {{ $product->vendor->whatsapp }}
                                </a>
                            </p>
                        @endif

                        @if($product->vendor->email)
                            <p class="flex items-center gap-2 text-gray-700">
                                <span>✉️</span>
                                <a href="mailto:{{ $product->vendor->email }}" class="hover:text-[#27ae60]">{{ $product->vendor->email }}</a>
                            </p>
                        @endif

                        @if($product->vendor->address)
                            <p class="flex items-start gap-2 text-gray-700">
                                <span>📍</span>
                                <span>{{ $product->vendor->address }}</span>
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

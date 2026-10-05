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

                @if($product->cjLink)
                    <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700">
                        🚚 {{ __('Ships from overseas · Estimated delivery :estimate', ['estimate' => $product->cjLink->shippingEstimate()]) }}
                    </p>
                @endif

                @if($product->description)
                    <div class="mt-5 text-sm text-gray-600 leading-relaxed whitespace-pre-line">
                        {{ $product->description }}
                    </div>
                @endif

                {{-- Buy / Inquire — opens a modal to start a WhatsApp/email conversation with the vendor --}}
                <div class="mt-6">
                    <button type="button" onclick="document.getElementById('inquiry-modal').classList.remove('hidden')"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-[#27ae60] px-5 py-3 text-sm font-semibold text-white hover:bg-[#1a7a44] transition-colors">
                        {{ __('Buy / Inquire') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Inquiry modal --}}
    @php
        $productUrl = route('shops.products.show', ['vendor' => $product->vendor->slug, 'product' => $product->slug]);
        $inquiryMessage = __(
            "Hi :vendor, I am interested in :product. Is it still available?\n\n:url",
            ['vendor' => $product->vendor->name, 'product' => $product->name, 'url' => $productUrl]
        );
        $inquirySubject = __('Inquiry about :product', ['product' => $product->name]);
    @endphp
    <div id="inquiry-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
         onclick="if (event.target === this) this.classList.add('hidden')">
        <div class="absolute inset-0 bg-black/50"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 sm:p-8">
            <button type="button"
                    onclick="document.getElementById('inquiry-modal').classList.add('hidden')"
                    aria-label="{{ __('Close') }}"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <h2 class="text-lg font-bold text-gray-900 mb-1">{{ __('Contact :shop', ['shop' => $product->vendor->name]) }}</h2>
            <p class="text-sm text-gray-500 mb-4">{{ __('Send a message to start the conversation.') }}</p>

            <label for="inquiry-message" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Your message') }}</label>
            <textarea id="inquiry-message" rows="5"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm mb-4 focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">{{ $inquiryMessage }}</textarea>

            <div class="space-y-2">
                @if($product->vendor->whatsapp)
                    <button type="button"
                            onclick="sendInquiry('whatsapp', '{{ preg_replace('/\D/', '', $product->vendor->whatsapp) }}')"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition-colors"
                            style="background:#25D366;">
                        💬 {{ __('Send via WhatsApp') }}
                    </button>
                @endif

                @if($product->vendor->email)
                    <button type="button"
                            onclick="sendInquiry('email', {{ json_encode($product->vendor->email) }}, {{ json_encode($inquirySubject) }})"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        ✉️ {{ __('Send via Email') }}
                    </button>
                @endif

                <a href="tel:{{ $product->vendor->phone }}"
                   class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    📞 {{ __('Call :phone', ['phone' => $product->vendor->phone]) }}
                </a>
            </div>

            @if($product->vendor->address)
                <p class="mt-4 flex items-start gap-2 text-sm text-gray-500">
                    <span>📍</span>
                    <span>{{ $product->vendor->address }}</span>
                </p>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function sendInquiry(channel, target, subject) {
        var message = document.getElementById('inquiry-message').value.trim();

        if (channel === 'whatsapp') {
            window.open('https://wa.me/' + target + '?text=' + encodeURIComponent(message), '_blank');
        } else if (channel === 'email') {
            window.location.href = 'mailto:' + target + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(message);
        }
    }
</script>
@endpush

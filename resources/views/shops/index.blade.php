@extends('layouts.public')

@section('title', __('Shops'))

@section('content')

    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('Shops') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ __('Vendors selling on :app', ['app' => config('app.name')]) }}</p>
    </div>

    @if($vendors->isEmpty())
        <p class="text-gray-500">{{ __('No shops yet.') }}</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($vendors as $vendor)
                <a href="{{ route('shops.show', $vendor->slug) }}"
                   class="group flex items-center gap-4 bg-white rounded-xl border border-gray-200 p-4 sm:p-5 hover:border-gray-300 hover:shadow-sm transition">
                    <div class="shrink-0 w-14 h-14 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center text-xl">
                        @if($vendor->logoUrl())
                            <img src="{{ $vendor->logoUrl() }}" alt="{{ $vendor->name }}" class="w-full h-full object-cover">
                        @else
                            🏪
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h2 class="font-semibold text-gray-900 group-hover:text-[#27ae60] transition-colors truncate">
                            {{ $vendor->name }}
                        </h2>
                        @if($vendor->city)
                            <p class="text-xs text-gray-500 mt-0.5">{{ $vendor->city }}</p>
                        @endif
                        <span class="mt-1.5 inline-flex items-center gap-1.5 text-xs font-medium rounded-full px-2.5 py-1 w-fit"
                              style="background: #e8f8f0; color: #27ae60;">
                            {{ $vendor->products_count }} {{ Str::plural(__('product'), $vendor->products_count) }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

@endsection

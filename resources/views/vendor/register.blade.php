@extends('layouts.public')

@section('title', __('Become a Vendor'))

@section('content')

    <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-8 max-w-2xl mx-auto">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1">{{ __('Sell With Us') }}</h1>
        <p class="text-sm text-gray-500 mb-6">{{ __('Set up your shop and start listing products. It goes live immediately.') }}</p>

        @if($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('vendor.register.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Shop Name') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('About Your Shop') }}</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">{{ old('description') }}</textarea>
            </div>

            <div>
                <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Logo') }}</label>
                <input type="file" id="logo" name="logo" accept="image/*"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                <p class="mt-1 text-xs text-gray-400">{{ __('JPG, PNG or WEBP, up to 2MB.') }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Phone') }}</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                </div>
                <div>
                    <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-1">{{ __('WhatsApp') }} <span class="text-gray-400 font-normal">({{ __('optional') }})</span></label>
                    <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email') }} <span class="text-gray-400 font-normal">({{ __('optional') }})</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                </div>
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">{{ __('City') }}</label>
                    <input type="text" id="city" name="city" value="{{ old('city') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                </div>
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Address') }}</label>
                <textarea id="address" name="address" rows="2"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">{{ old('address') }}</textarea>
                <p class="mt-1 text-xs text-gray-400">{{ __('Shown to buyers on the Buy / Inquire panel.') }}</p>
            </div>

            <div>
                <label for="website" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Website') }} <span class="text-gray-400 font-normal">({{ __('optional') }})</span></label>
                <input type="url" id="website" name="website" value="{{ old('website') }}" placeholder="https://"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-[#27ae60] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors text-center">
                    {{ __('Create My Shop') }}
                </button>
                <a href="{{ route('home') }}"
                   class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors text-center">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>

@endsection

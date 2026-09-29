@extends('layouts.vendor')

@section('title', 'Shop Profile')

@section('content')

    <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-8 max-w-2xl">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">Shop Profile</h1>

        @if($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('vendor.shop.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Shop Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $vendor->name) }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">About Your Shop</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">{{ old('description', $vendor->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                @if($vendor->logoUrl())
                    <div class="flex items-center gap-3 mb-2">
                        <img src="{{ $vendor->logoUrl() }}" alt="{{ $vendor->name }}" class="w-14 h-14 rounded-full object-cover border border-gray-200">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="remove_logo" value="1"> Remove current logo
                        </label>
                    </div>
                @endif
                <input type="file" id="logo" name="logo" accept="image/*"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                <p class="mt-1 text-xs text-gray-400">JPG, PNG or WEBP, up to 2MB.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $vendor->phone) }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                </div>
                <div>
                    <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                    <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $vendor->whatsapp) }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $vendor->email) }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                </div>
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" id="city" name="city" value="{{ old('city', $vendor->city) }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                </div>
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea id="address" name="address" rows="2"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">{{ old('address', $vendor->address) }}</textarea>
                <p class="mt-1 text-xs text-gray-400">Shown to buyers on the Buy / Inquire panel.</p>
            </div>

            <div>
                <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                <input type="url" id="website" name="website" value="{{ old('website', $vendor->website) }}" placeholder="https://"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-[#27ae60] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors text-center">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

@endsection

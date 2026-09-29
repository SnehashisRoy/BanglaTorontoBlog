@extends('layouts.vendor')

@section('title', 'Edit Product')

@section('content')

    <div class="mb-6">
        <a href="{{ route('vendor.products.index') }}" class="text-sm text-[#27ae60] hover:text-[#1a7a44]">&larr; Back to products</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-8 max-w-2xl">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">Edit Product</h1>

        @if($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('vendor.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="product_category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select id="product_category_id" name="product_category_id"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                        <option value="">— None —</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('product_category_id', $product->product_category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price <span class="text-gray-400 font-normal">(leave blank for "contact for price")</span></label>
                    <input type="number" id="price" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                </div>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                    <option value="draft" {{ old('status', $product->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status', $product->status) === 'published' ? 'selected' : '' }}>Published</option>
                </select>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="5"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">{{ old('description', $product->description) }}</textarea>
            </div>

            @if($product->images->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Photos</label>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                        @foreach($product->images as $image)
                            <label class="relative block rounded-lg overflow-hidden border border-gray-200 cursor-pointer group">
                                <img src="{{ $image->url() }}" alt="" class="w-full aspect-square object-cover">
                                <div class="absolute inset-0 bg-black/0 group-has-[:checked]:bg-black/50 transition-colors flex items-center justify-center">
                                    <span class="hidden group-has-[:checked]:inline text-white text-xs font-semibold">Remove</span>
                                </div>
                                <input type="checkbox" name="delete_image_ids[]" value="{{ $image->id }}" class="absolute top-1.5 right-1.5">
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Check a photo to remove it when you save.</p>
                </div>
            @endif

            <div>
                <label for="images" class="block text-sm font-medium text-gray-700 mb-1">Add Photos</label>
                <input type="file" id="images" name="images[]" accept="image/*" multiple
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                <p class="mt-1 text-xs text-gray-400">JPG, PNG or WEBP, up to 2MB each, up to 8 photos total.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-[#27ae60] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors text-center">
                    Save Changes
                </button>
                <a href="{{ route('vendor.products.index') }}"
                   class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>

@endsection

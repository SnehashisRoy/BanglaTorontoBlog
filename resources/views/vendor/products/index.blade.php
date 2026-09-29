@extends('layouts.vendor')

@section('title', 'My Products')

@section('content')

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">My Products</h1>
        <a href="{{ route('vendor.products.create') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-[#27ae60] px-3.5 py-1.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors">
            + New Product
        </a>
    </div>

    @if($products->isEmpty())
        <p class="text-gray-500">No products yet.
            <a href="{{ route('vendor.products.create') }}" class="text-[#27ae60] hover:underline">Create the first one.</a>
        </p>
    @else
        {{-- Mobile: stacked cards --}}
        <div class="sm:hidden space-y-3">
            @foreach($products as $product)
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-medium text-gray-900 leading-snug">{{ $product->name }}</p>
                        @if($product->status === 'published')
                            <span class="shrink-0 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Published</span>
                        @else
                            <span class="shrink-0 rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700">Draft</span>
                        @endif
                    </div>
                    <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
                        <span>{{ $product->category->name ?? '—' }}</span>
                        <span>&middot;</span>
                        <span>{{ $product->price !== null ? '$'.number_format((float) $product->price, 2) : 'Contact for price' }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('vendor.products.edit', $product) }}"
                           class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 transition-colors">
                            ✏️ Edit
                        </a>
                        <form action="{{ route('vendor.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100 transition-colors">
                                🗑 Delete
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="hidden sm:block bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Name</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Category</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Price</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($products as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900 max-w-xs truncate">{{ $product->name }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $product->category->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600">
                                {{ $product->price !== null ? '$'.number_format((float) $product->price, 2) : 'Contact for price' }}
                            </td>
                            <td class="px-5 py-3">
                                @if($product->status === 'published')
                                    <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Published</span>
                                @else
                                    <span class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700">Draft</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('vendor.products.edit', $product) }}"
                                       class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 transition-colors whitespace-nowrap">
                                        ✏️ Edit
                                    </a>
                                    <form action="{{ route('vendor.products.destroy', $product) }}" method="POST"
                                          class="inline" onsubmit="return confirm('Delete this product?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100 transition-colors whitespace-nowrap">
                                            🗑 Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $products->links() }}</div>
    @endif

@endsection

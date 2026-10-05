@extends('layouts.admin')

@section('title', 'Admin — Products')

@section('content')

    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Products</h1>
        <p class="text-sm text-gray-500 mt-1">Feature a product to highlight it on the home page.</p>
    </div>

    <form action="{{ route('admin.products.index') }}" method="GET" class="mb-5 flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name&hellip;"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 w-56">
        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="featured" value="1" {{ request()->boolean('featured') ? 'checked' : '' }}
                   class="rounded border-gray-300 text-[#27ae60] focus:ring-[#27ae60]">
            Featured only
        </label>
        <button type="submit"
                class="rounded-lg border border-gray-300 px-3.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            Filter
        </button>
    </form>

    @if($products->isEmpty())
        <p class="text-gray-500">No products found.</p>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Product</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Vendor</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Category</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Price</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Featured</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($products as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900 max-w-xs truncate">{{ $product->name }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $product->vendor->name }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $product->category?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600 whitespace-nowrap">{{ $product->price !== null ? '$'.number_format((float) $product->price, 2) : '—' }}</td>
                            <td class="px-5 py-3">
                                @if($product->status === 'published')
                                    <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Published</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">{{ ucfirst($product->status) }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($product->is_featured)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">★ Featured</span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($product->is_featured)
                                    <form action="{{ route('admin.products.unfeature', $product) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 transition-colors whitespace-nowrap">
                                            Remove Feature
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.products.feature', $product) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100 transition-colors whitespace-nowrap">
                                            ★ Feature
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $products->links() }}
        </div>
    @endif

@endsection

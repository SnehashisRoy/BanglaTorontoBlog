@extends('layouts.admin')

@section('title', 'Admin — Product Categories')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Product Categories</h1>
            <p class="text-sm text-gray-500 mt-1">Categories vendors assign to their products.</p>
        </div>
        <a href="{{ route('admin.product-categories.create') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-[#27ae60] px-3.5 py-1.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors whitespace-nowrap">
            + New Category
        </a>
    </div>

    @if($categories->isEmpty())
        <p class="text-gray-500">No categories yet.</p>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Name</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Slug</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Products</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($categories as $category)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $category->name }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $category->slug }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $category->products_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.product-categories.edit', $category) }}"
                                       class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-100 transition-colors whitespace-nowrap">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.product-categories.destroy', $category) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete this category? Products using it will keep their data but lose this category.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100 transition-colors whitespace-nowrap">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

@endsection

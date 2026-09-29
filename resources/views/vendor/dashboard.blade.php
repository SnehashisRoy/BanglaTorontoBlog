@extends('layouts.vendor')

@section('title', 'Dashboard')

@section('content')

    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $vendor->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">Welcome back.</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['published'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Published products</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['draft'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Draft products</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('vendor.products.create') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-[#27ae60] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors">
            + New Product
        </a>
        <a href="{{ route('vendor.products.index') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            Manage Products
        </a>
        <a href="{{ route('vendor.shop.edit') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            Edit Shop Profile
        </a>
        <a href="{{ route('shops.show', $vendor->slug) }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            View My Shop ↗
        </a>
    </div>

@endsection

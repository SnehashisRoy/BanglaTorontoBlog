@extends('layouts.admin')

@section('title', 'Admin — Vendors')

@section('content')

    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Vendors</h1>
        <p class="text-sm text-gray-500 mt-1">Approve a shop to make it and its published products visible on the site.</p>
    </div>

    @if($vendors->isEmpty())
        <p class="text-gray-500">No vendor requests yet.</p>
    @else
        {{-- Mobile: stacked cards --}}
        <div class="sm:hidden space-y-3">
            @foreach($vendors as $vendor)
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium text-gray-900 leading-snug">{{ $vendor->name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $vendor->user->email }}</p>
                        </div>
                        @if($vendor->isApproved())
                            <span class="shrink-0 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Approved</span>
                        @else
                            <span class="shrink-0 rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700">Pending</span>
                        @endif
                    </div>
                    <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
                        <span>{{ $vendor->phone }}</span>
                        <span>&middot;</span>
                        <span>{{ $vendor->products_count }} {{ Str::plural('product', $vendor->products_count) }}</span>
                        <span>&middot;</span>
                        <span>{{ $vendor->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($vendor->isApproved())
                            <a href="{{ route('shops.show', $vendor->slug) }}" target="_blank"
                               class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-100 transition-colors">
                                👁 View
                            </a>
                            <form action="{{ route('admin.vendors.mark-pending', $vendor) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg bg-yellow-50 px-3 py-1.5 text-xs font-medium text-yellow-700 hover:bg-yellow-100 transition-colors">
                                    ⏸ Move to Pending
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.vendors.approve', $vendor) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700 hover:bg-green-100 transition-colors">
                                    ✅ Approve
                                </button>
                            </form>
                        @endif

                        @if($vendor->cj_dropshipping_enabled)
                            <form action="{{ route('admin.vendors.disable-cj', $vendor) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 transition-colors">
                                    📦 CJ: On
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.vendors.enable-cj', $vendor) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 transition-colors">
                                    📦 CJ: Off
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="hidden sm:block bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Shop</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Owner</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Contact</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Products</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Requested</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">CJ Dropshipping</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($vendors as $vendor)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900 max-w-xs truncate">{{ $vendor->name }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $vendor->user->email }}</td>
                            <td class="px-5 py-3 text-gray-600 whitespace-nowrap">{{ $vendor->phone }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $vendor->products_count }}</td>
                            <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $vendor->created_at->format('M d, Y') }}</td>
                            <td class="px-5 py-3">
                                @if($vendor->isApproved())
                                    <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Approved</span>
                                @else
                                    <span class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700">Pending</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($vendor->cj_dropshipping_enabled)
                                    <form action="{{ route('admin.vendors.disable-cj', $vendor) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700 hover:bg-indigo-200 transition-colors">
                                            Enabled
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.vendors.enable-cj', $vendor) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 hover:bg-gray-200 transition-colors">
                                            Disabled
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    @if($vendor->isApproved())
                                        <a href="{{ route('shops.show', $vendor->slug) }}" target="_blank"
                                           class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-100 transition-colors whitespace-nowrap">
                                            👁 View
                                        </a>
                                        <form action="{{ route('admin.vendors.mark-pending', $vendor) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-yellow-50 px-3 py-1.5 text-xs font-medium text-yellow-700 hover:bg-yellow-100 transition-colors whitespace-nowrap">
                                                ⏸ Move to Pending
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.vendors.approve', $vendor) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700 hover:bg-green-100 transition-colors whitespace-nowrap">
                                                ✅ Approve
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

@endsection

<!DOCTYPE html>
<html lang="{{ app()->getLocale() ?: 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'My Shop') &mdash; {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    @vite(['resources/css/app.css'])
    @stack('head')
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col antialiased">

    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-6">
                    <a href="{{ route('home') }}" class="shrink-0">
                        <img src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }}" class="h-10 w-auto">
                    </a>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">{{ __('Vendor') }}</span>
                </div>

                <nav class="flex items-center gap-4 text-sm font-medium text-gray-600">
                    <a href="{{ route('vendor.dashboard') }}"
                       class="px-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors {{ request()->routeIs('vendor.dashboard') ? 'bg-gray-100 text-gray-900' : '' }}">
                        {{ __('Dashboard') }}
                    </a>
                    <a href="{{ route('vendor.products.index') }}"
                       class="px-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors {{ request()->routeIs('vendor.products.*') ? 'bg-gray-100 text-gray-900' : '' }}">
                        {{ __('Products') }}
                    </a>
                    <a href="{{ route('vendor.shop.edit') }}"
                       class="px-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors {{ request()->routeIs('vendor.shop.*') ? 'bg-gray-100 text-gray-900' : '' }}">
                        {{ __('Shop Profile') }}
                    </a>
                    <a href="{{ route('vendor.products.create') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-[#27ae60] px-3.5 py-1.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors">
                        + {{ __('New Product') }}
                    </a>
                    <a href="{{ route('home') }}"
                       class="px-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors text-gray-500">
                        ← {{ __('Site') }}
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">

        @if(session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')

    </main>

    <footer class="border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-sm text-gray-400">
            &copy; {{ date('Y') }} {{ config('app.name') }} &mdash; {{ __('Vendor') }}
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

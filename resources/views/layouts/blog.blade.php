@php $locale = app()->getLocale() ?: 'en'; @endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('Blog')) &mdash; {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col antialiased">

    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-24 sm:h-28">
                <a href="{{ route('blog.index', ['locale' => $locale]) }}" class="shrink-0">
                    <img src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }}" class="h-16 sm:h-24 w-auto">
                </a>

                {{-- Desktop nav --}}
                <nav class="hidden sm:flex items-center gap-5 text-sm font-medium text-gray-600">
                    <a href="{{ route('blog.index', ['locale' => $locale]) }}" class="hover:text-gray-900">{{ __('Blog') }}</a>
                    <a href="{{ route('companies.index') }}"
                       class="font-semibold transition-colors"
                       style="color: #27ae60;"
                       onmouseover="this.style.color='#1a7a44'"
                       onmouseout="this.style.color='#27ae60'">{{ __('Businesses') }}</a>

                    @auth
                        @if(auth()->user()->is_admin)
                            <a href="{{ route('admin.posts.index') }}" class="hover:text-gray-900">{{ __('Admin') }}</a>
                        @endif
                    @endauth

                    @if($showLocaleSwitch ?? false)
                    @php
                        $enUrl = isset($switcherSlug)
                            ? route('blog.show', ['locale' => 'en', 'slug' => $switcherSlug])
                            : route('blog.index', ['locale' => 'en']);
                        $bnUrl = isset($switcherSlug)
                            ? route('blog.show', ['locale' => 'bn', 'slug' => $switcherSlug])
                            : route('blog.index', ['locale' => 'bn']);
                    @endphp
                    <div class="flex items-center gap-1 border border-gray-200 rounded-lg overflow-hidden text-xs">
                        <a href="{{ $enUrl }}"
                           class="px-2.5 py-1.5 transition-colors {{ $locale === 'en' ? 'bg-[#27ae60] text-white' : 'hover:bg-gray-100' }}">
                            EN
                        </a>
                        <a href="{{ $bnUrl }}"
                           class="px-2.5 py-1.5 transition-colors {{ $locale === 'bn' ? 'bg-[#27ae60] text-white' : 'hover:bg-gray-100' }}">
                            বাং
                        </a>
                    </div>
                    @endif
                </nav>

                {{-- Mobile hamburger --}}
                <button type="button"
                        class="sm:hidden -mr-1 p-2 rounded-lg text-gray-600 hover:bg-gray-100"
                        onclick="document.getElementById('mobile-nav').classList.toggle('hidden')"
                        aria-label="{{ __('Toggle menu') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            {{-- Mobile nav panel --}}
            <nav id="mobile-nav" class="hidden sm:hidden border-t border-gray-200 py-3 space-y-3 text-sm font-medium text-gray-600">
                <a href="{{ route('blog.index', ['locale' => $locale]) }}" class="block py-1.5 hover:text-gray-900">{{ __('Blog') }}</a>
                <a href="{{ route('companies.index') }}"
                   class="block py-1.5 font-semibold"
                   style="color: #27ae60;">{{ __('Businesses') }}</a>

                @auth
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.posts.index') }}" class="block py-1.5 hover:text-gray-900">{{ __('Admin') }}</a>
                    @endif
                @endauth

                @if($showLocaleSwitch ?? false)
                <div class="flex items-center gap-1 pt-1 border-t border-gray-100">
                    <a href="{{ $enUrl ?? route('blog.index', ['locale' => 'en']) }}"
                       class="rounded-lg px-3 py-1.5 text-xs transition-colors {{ $locale === 'en' ? 'bg-[#27ae60] text-white' : 'border border-gray-200 hover:bg-gray-100' }}">
                        EN
                    </a>
                    <a href="{{ $bnUrl ?? route('blog.index', ['locale' => 'bn']) }}"
                       class="rounded-lg px-3 py-1.5 text-xs transition-colors {{ $locale === 'bn' ? 'bg-[#27ae60] text-white' : 'border border-gray-200 hover:bg-gray-100' }}">
                        বাং
                    </a>
                </div>
                @endif
            </nav>
        </div>
    </header>

    <main class="flex-1 max-w-3xl mx-auto w-full px-4 sm:px-6 py-8 sm:py-12">

        @if(session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')

    </main>

    <footer class="border-t border-gray-200 mt-auto">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 text-sm text-gray-400 text-center">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </div>
    </footer>

</body>
</html>

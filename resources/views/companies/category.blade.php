@extends('layouts.blog')

@section('title', $label . ' — Business Directory')

@section('content')

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm mb-6" aria-label="Breadcrumb">
        <a href="{{ route('companies.index') }}"
           class="font-medium transition-colors"
           style="color: #27ae60;"
           onmouseover="this.style.color='#1a7a44'"
           onmouseout="this.style.color='#27ae60'">
            Business Directory
        </a>
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-500 truncate">{{ $label }}</span>
    </nav>

    {{-- Category header --}}
    <div class="rounded-2xl px-6 py-7 sm:px-8 sm:py-9 mb-8"
         style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0;">
        <div class="flex items-center gap-4">
            <span class="text-4xl sm:text-5xl leading-none">{{ $icon }}</span>
            <div>
                <h1 class="text-xl sm:text-3xl font-bold" style="color: #1a1a1a;">{{ $label }}</h1>
                <p class="mt-1 text-sm" style="color: #27ae60;">
                    {{ $companies->count() }} {{ $companies->count() === 1 ? 'business' : 'businesses' }} listed
                </p>
            </div>
        </div>
    </div>

    {{-- Business grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach($companies as $co)
            <a href="{{ $co->company_slug ? route('companies.show', [$co->slug, $co->company_slug]) : '#' }}"
               class="group block bg-white rounded-xl border border-gray-200 p-5
                      hover:border-transparent hover:shadow-md transition-all duration-200"
               onmouseover="this.style.boxShadow='0 6px 24px rgba(46,204,113,0.16)'; this.style.borderColor='transparent';"
               onmouseout="this.style.boxShadow=''; this.style.borderColor='';">

                {{-- Name + arrow --}}
                <div class="flex items-start justify-between gap-3 mb-3">
                    <h2 class="font-semibold text-gray-900 leading-snug text-sm sm:text-base
                               group-hover:text-[#27ae60] transition-colors">
                        {{ $co->company ?: '—' }}
                    </h2>
                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-gray-300 group-hover:text-[#2ecc71] transition-colors"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>

                {{-- Service snippet --}}
                @if($co->service)
                    <p class="text-xs text-gray-500 leading-relaxed line-clamp-2 mb-3">
                        {{ $co->service }}
                    </p>
                @endif

                {{-- Contact pill --}}
                @if($co->contact)
                    <div class="flex items-center gap-1.5 text-xs font-medium rounded-full px-2.5 py-1 w-fit"
                         style="background: #e8f8f0; color: #1a7a44;">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span class="truncate max-w-[180px]">{{ $co->contact }}</span>
                    </div>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Back link --}}
    <div class="mt-8 pt-6 border-t border-gray-200">
        <a href="{{ route('companies.index') }}"
           class="inline-flex items-center gap-2 text-sm font-medium transition-colors"
           style="color: #27ae60;"
           onmouseover="this.style.color='#1a7a44'"
           onmouseout="this.style.color='#27ae60'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            All categories
        </a>
    </div>

@endsection

@extends('layouts.blog')

@section('title', ($company->company ?: $categoryLabel) . ' — Business Directory')

@section('content')

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm mb-6 flex-wrap" aria-label="Breadcrumb">
        <a href="{{ route('companies.index') }}"
           class="font-medium transition-colors shrink-0"
           style="color: #27ae60;"
           onmouseover="this.style.color='#1a7a44'"
           onmouseout="this.style.color='#27ae60'">
            Business Directory
        </a>
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <a href="{{ route('companies.category', $company->slug) }}"
           class="font-medium transition-colors shrink-0"
           style="color: #27ae60;"
           onmouseover="this.style.color='#1a7a44'"
           onmouseout="this.style.color='#27ae60'">
            {{ $categoryLabel }}
        </a>
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-500 truncate">{{ $company->company }}</span>
    </nav>

    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">

        {{-- Header band --}}
        <div class="h-2" style="background: linear-gradient(90deg, #2ecc71, #27ae60, #c0392b);"></div>

        <div class="p-6 sm:p-8">

            {{-- Business name + category badge --}}
            <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold leading-tight" style="color: #1a1a1a;">
                        {{ $company->company ?: $categoryLabel }}
                    </h1>
                    <div class="mt-2">
                        <a href="{{ route('companies.category', $company->slug) }}"
                           class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-full px-3 py-1.5 transition-colors"
                           style="background: #e8f8f0; color: #1a7a44;"
                           onmouseover="this.style.background='#bbf7d0'"
                           onmouseout="this.style.background='#e8f8f0'">
                            <span>{{ $company->categoryIcon() }}</span>
                            {{ $categoryLabel }}
                        </a>
                    </div>
                </div>

                {{-- Decorative emblem --}}
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full flex items-center justify-center shrink-0 text-2xl"
                     style="background: linear-gradient(135deg, #e8f8f0, #dcfce7); border: 2px solid #bbf7d0;">
                    {{ $company->categoryIcon() }}
                </div>
            </div>

            {{-- Divider --}}
            <hr class="border-gray-100 mb-6">

            {{-- Services section --}}
            @if($company->service)
                <section class="mb-6">
                    <h2 class="text-xs font-semibold tracking-widest uppercase mb-3" style="color: #27ae60;">
                        Services
                    </h2>
                    <p class="text-gray-700 leading-relaxed">{{ $company->service }}</p>
                </section>
            @endif

            @if($company->description && $company->description !== $company->service)
                <section class="mb-6">
                    <h2 class="text-xs font-semibold tracking-widest uppercase mb-3" style="color: #27ae60;">
                        About
                    </h2>
                    <p class="text-gray-700 leading-relaxed">{{ $company->description }}</p>
                </section>
            @endif

            {{-- Contact section --}}
            @if($company->contact)
                <section class="rounded-xl p-5 sm:p-6" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                    <h2 class="text-xs font-semibold tracking-widest uppercase mb-4" style="color: #27ae60;">
                        Contact Information
                    </h2>
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                              style="background: #2ecc71;">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </span>
                        <p class="text-gray-800 leading-relaxed text-sm sm:text-base">{{ $company->contact }}</p>
                    </div>
                </section>
            @endif

        </div>
    </div>

    {{-- Back to category --}}
    <div class="mt-8 pt-6 border-t border-gray-200 flex items-center gap-4">
        <a href="{{ route('companies.category', $company->slug) }}"
           class="inline-flex items-center gap-2 text-sm font-medium transition-colors"
           style="color: #27ae60;"
           onmouseover="this.style.color='#1a7a44'"
           onmouseout="this.style.color='#27ae60'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to {{ $categoryLabel }}
        </a>
    </div>

@endsection

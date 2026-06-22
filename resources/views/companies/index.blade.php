@extends('layouts.blog')

@section('title', 'Bengali Business Directory — Toronto')

@section('content')

    {{-- Hero banner --}}
    <div class="relative rounded-2xl overflow-hidden mb-10"
         style="background: linear-gradient(135deg, #1a1a1a 0%, #27ae60 100%);">
        <div class="absolute inset-0 opacity-10"
             style="background-image: repeating-linear-gradient(45deg, #2ecc71 0, #2ecc71 1px, transparent 0, transparent 50%); background-size: 20px 20px;"></div>
        <div class="relative px-6 py-10 sm:px-10 sm:py-14 text-white">
            <div class="flex items-center gap-3 mb-3">
                <span class="text-3xl">🏢</span>
                <span class="text-xs font-semibold tracking-widest uppercase"
                      style="color: #2ecc71;">Bengali Community Toronto</span>
            </div>
            <h1 class="text-2xl sm:text-4xl font-bold leading-tight mb-3">
                Business Directory
            </h1>
            <p class="text-sm sm:text-base opacity-80 max-w-lg">
                Find trusted Bengali-owned and Bengali-speaking businesses across Toronto and the GTA.
            </p>
            <div class="mt-5 flex items-center gap-2 text-xs opacity-60">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                {{ $categories->sum('count') }} businesses across {{ $categories->count() }} categories
            </div>
        </div>
    </div>

    {{-- Category grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        @foreach($categories as $cat)
            <a href="{{ route('companies.category', $cat->slug) }}"
               class="group relative bg-white rounded-xl border border-gray-200 p-4 sm:p-5
                      hover:border-transparent hover:shadow-lg transition-all duration-200
                      flex flex-col gap-2"
               style="--hover-shadow: 0 8px 30px rgba(46,204,113,0.18);"
               onmouseover="this.style.boxShadow='0 8px 30px rgba(46,204,113,0.18)'"
               onmouseout="this.style.boxShadow=''">

                {{-- Top accent bar --}}
                <span class="absolute top-0 left-0 right-0 h-1 rounded-t-xl opacity-0 group-hover:opacity-100 transition-opacity"
                      style="background: linear-gradient(90deg, #2ecc71, #27ae60);"></span>

                <span class="text-2xl sm:text-3xl leading-none">{{ $cat->icon }}</span>

                <span class="font-semibold text-gray-900 text-sm sm:text-base leading-tight
                             group-hover:text-[#1a7a44] transition-colors">
                    {{ $cat->label }}
                </span>

                <span class="mt-auto inline-flex items-center gap-1.5 text-xs font-medium rounded-full px-2.5 py-1 w-fit"
                      style="background: #e8f8f0; color: #27ae60;">
                    {{ $cat->count }} {{ $cat->count === 1 ? 'business' : 'businesses' }}
                </span>
            </a>
        @endforeach
    </div>

@endsection

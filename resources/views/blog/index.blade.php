@extends('layouts.blog')

@section('title', __('All Posts'))

@section('content')

    {{-- Hero banner --}}
    <div class="relative rounded-2xl overflow-hidden mb-10"
         style="background: linear-gradient(135deg, #1a1a1a 0%, #27ae60 100%);">
        <div class="absolute inset-0 opacity-10"
             style="background-image: repeating-linear-gradient(45deg, #2ecc71 0, #2ecc71 1px, transparent 0, transparent 50%); background-size: 20px 20px;"></div>
        <div class="relative px-6 py-10 sm:px-10 sm:py-14 text-white">
            <div class="flex items-center gap-3 mb-3">
                <span class="text-3xl">📰</span>
                <span class="text-xs font-semibold tracking-widest uppercase"
                      style="color: #2ecc71;">BanglaToronto</span>
            </div>
            <h1 class="text-2xl sm:text-4xl font-bold leading-tight mb-3">
                {{ __('Information helps you prosper') }}
            </h1>
            <p class="text-sm sm:text-base opacity-80 max-w-lg">
                {{ __('News, deals, and resources for the Bengali community in Toronto and the GTA.') }}
            </p>
            <div class="mt-5 flex items-center gap-2 text-xs opacity-60">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h6"/>
                </svg>
                {{ $posts->total() }} {{ Str::plural(__('post'), $posts->total()) }}
            </div>
        </div>
    </div>

    @if($posts->isEmpty())
        <p class="text-gray-500">{{ __('No posts published yet.') }}</p>
    @else
        <div class="space-y-4 sm:space-y-5">
            @foreach($posts as $post)
                @php $t = $post->translations->first() @endphp
                <article class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:border-gray-300 hover:shadow-sm transition">
                    @if($post->image_path)
                        <a href="{{ route('blog.show', ['slug' => $post->slug]) }}" class="block">
                            <img src="{{ $post->imageUrl() }}" alt="{{ $t?->title }}"
                                 class="w-full h-44 sm:h-52 object-cover">
                        </a>
                    @endif
                    <div class="p-5 sm:p-6">
                        <div class="flex items-center gap-2 mb-3 text-xs text-gray-500">
                            @if($post->category)
                                <span class="rounded-full px-2.5 py-1 font-medium rounded-full" style="background:#e8f8f0; color:#27ae60;">
                                    {{ $post->category->name }}
                                </span>
                            @endif
                            <span class="ml-auto whitespace-nowrap">{{ $post->created_at->format('M d, Y') }}</span>
                        </div>

                        <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2 leading-snug">
                            <a href="{{ route('blog.show', ['slug' => $post->slug]) }}"
                               class="transition-colors hover:text-[#27ae60]">
                                {{ $t?->title }}
                            </a>
                        </h2>

                        <p class="text-gray-600 text-sm leading-relaxed line-clamp-3">
                            {{ Str::limit(strip_tags($t?->body ?? ''), 200) }}
                        </p>

                        <a href="{{ route('blog.show', ['slug' => $post->slug]) }}"
                           class="mt-4 inline-flex items-center text-sm font-medium text-[#27ae60] hover:text-[#1a7a44]">
                            {{ __('Read more') }} &rarr;
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">{{ $posts->links() }}</div>
    @endif

@endsection

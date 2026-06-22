@extends('layouts.blog')

@section('title', __('All Posts'))

@section('content')

    <div class="mb-8 sm:mb-10">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">{{ __('Latest Posts') }}</h1>
        <p class="mt-1.5 text-gray-500 text-sm">
            {{ $posts->total() }} {{ Str::plural(__('post'), $posts->total()) }}
        </p>
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
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 font-medium text-indigo-700">
                                    {{ $post->category->name }}
                                </span>
                            @endif
                            <span class="ml-auto whitespace-nowrap">{{ $post->created_at->format('M d, Y') }}</span>
                        </div>

                        <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2 leading-snug">
                            <a href="{{ route('blog.show', ['slug' => $post->slug]) }}"
                               class="hover:text-indigo-600 transition-colors">
                                {{ $t?->title }}
                            </a>
                        </h2>

                        <p class="text-gray-600 text-sm leading-relaxed line-clamp-3">
                            {{ Str::limit(strip_tags($t?->body ?? ''), 200) }}
                        </p>

                        <a href="{{ route('blog.show', ['slug' => $post->slug]) }}"
                           class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            {{ __('Read more') }} &rarr;
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">{{ $posts->links() }}</div>
    @endif

@endsection

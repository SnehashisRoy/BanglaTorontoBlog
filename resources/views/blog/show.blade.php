@extends('layouts.blog')

@php $t = $post->translations->first() @endphp

@section('title', $t?->title ?? $post->slug)

@section('content')

    <div class="mb-6">
        <a href="{{ route('blog.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
            &larr; {{ __('Back to posts') }}
        </a>
    </div>

    <article class="bg-white rounded-xl border border-gray-200 overflow-hidden">

        @if($post->image_path)
            <img src="{{ $post->imageUrl() }}" alt="{{ $t?->title }}" class="w-full h-56 sm:h-80 object-cover">
        @endif

        <div class="p-5 sm:p-8">

            <div class="flex items-center gap-2 mb-4 text-xs text-gray-500">
                @if($post->category)
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 font-medium text-indigo-700">
                        {{ $post->category->name }}
                    </span>
                @endif
                <span class="ml-auto whitespace-nowrap">{{ $post->created_at->format('F d, Y') }}</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6 leading-tight tracking-tight">{{ $t?->title }}</h1>

            <div class="prose prose-sm sm:prose-base prose-gray max-w-none prose-p:leading-relaxed prose-a:text-indigo-600">
                {!! $t?->body !!}
            </div>

        </div>
    </article>

@endsection

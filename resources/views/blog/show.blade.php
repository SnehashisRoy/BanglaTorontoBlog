@extends('layouts.blog')

@php $t = $post->translations->first() @endphp

@section('title', $t?->title ?? $post->slug)

@section('content')

    <div class="mb-6 flex items-center justify-between gap-3">
        <a href="{{ route('blog.index') }}" class="text-sm text-[#27ae60] hover:text-[#1a7a44]">
            &larr; {{ __('Back to posts') }}
        </a>

        @auth
            @if(auth()->user()->is_admin)
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.posts.edit', $post) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                    </a>
                    <form action="{{ route('admin.posts.destroy', $post) }}" method="POST"
                          onsubmit="return confirm('Delete this post?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete
                        </button>
                    </form>
                </div>
            @endif
        @endauth
    </div>

    <article class="bg-white rounded-xl border border-gray-200 overflow-hidden">

        @if($post->image_path)
            <img src="{{ $post->imageUrl() }}" alt="{{ $t?->title }}" class="w-full h-56 sm:h-80 object-cover">
        @endif

        <div class="p-5 sm:p-8">

            <div class="flex items-center gap-2 mb-4 text-xs text-gray-500">
                @if($post->category)
                    <span class="rounded-full px-2.5 py-1 font-medium" style="background:#e8f8f0; color:#27ae60;">
                        {{ $post->category->name }}
                    </span>
                @endif
                <span class="ml-auto whitespace-nowrap">{{ $post->created_at->format('F d, Y') }}</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6 leading-tight tracking-tight">{{ $t?->title }}</h1>

            <div class="prose prose-sm sm:prose-base prose-gray max-w-none prose-p:leading-relaxed prose-a:text-[#27ae60]">
                {!! $t?->body !!}
            </div>

            <div class="mt-8 pt-6 border-t border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">{{ __('Share this post') }}</p>
                <div class="flex flex-wrap items-center gap-2">

                    {{-- Facebook --}}
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90"
                       style="background:#1877f2">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.883v2.27h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
                        Facebook
                    </a>

                    {{-- WhatsApp --}}
                    <a href="https://wa.me/?text={{ urlencode($t?->title . ' ' . request()->url()) }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90"
                       style="background:#25d366">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                        WhatsApp
                    </a>

                    {{-- X (Twitter) --}}
                    <a href="https://x.com/intent/tweet?text={{ urlencode($t?->title) }}&url={{ urlencode(request()->url()) }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90"
                       style="background:#000">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        X
                    </a>

                    {{-- Copy link --}}
                    <button onclick="copyLink(this)"
                            data-url="{{ request()->url() }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        <span>{{ __('Copy link') }}</span>
                    </button>

                </div>
            </div>

        </div>
    </article>

@endsection

@push('scripts')
<script>
function copyLink(btn) {
    navigator.clipboard.writeText(btn.dataset.url).then(function () {
        var span = btn.querySelector('span');
        span.textContent = '{{ __("Copied!") }}';
        setTimeout(function () { span.textContent = '{{ __("Copy link") }}'; }, 2000);
    });
}
</script>
@endpush

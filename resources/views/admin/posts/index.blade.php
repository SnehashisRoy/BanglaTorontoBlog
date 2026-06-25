@extends('layouts.admin')

@section('title', 'Admin — Posts')

@section('content')

    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Posts</h1>
    </div>

    @if($posts->isEmpty())
        <p class="text-gray-500">No posts yet.
            <a href="{{ route('admin.posts.create') }}" class="text-[#27ae60] hover:underline">Create the first one.</a>
        </p>
    @else
        {{-- Mobile: stacked cards --}}
        <div class="sm:hidden space-y-3">
            @foreach($posts as $post)
                @php
                    $enTitle = $post->translation('en')?->title ?? '—';
                    $hasEn   = $post->translation('en') !== null;
                    $hasBn   = $post->translation('bn') !== null;
                @endphp
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-medium text-gray-900 leading-snug">{{ $enTitle }}</p>
                        @if($post->status === 'published')
                            <span class="shrink-0 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Published</span>
                        @else
                            <span class="shrink-0 rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700">Draft</span>
                        @endif
                    </div>
                    <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
                        <span>{{ $post->category->name ?? '—' }}</span>
                        <span>&middot;</span>
                        <span>{{ $post->created_at->format('M d, Y') }}</span>
                        <span class="ml-auto inline-flex items-center gap-1">
                            <span class="rounded px-1.5 py-0.5 font-medium {{ $hasEn ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">EN</span>
                            <span class="rounded px-1.5 py-0.5 font-medium {{ $hasBn ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">BN</span>
                        </span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('admin.posts.edit', $post) }}"
                           class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 transition-colors">
                            ✏️ Edit
                        </a>
                        @if($post->status === 'published')
                        <a href="{{ route('blog.show', ['locale' => 'en', 'slug' => $post->slug]) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-100 transition-colors">
                            👁 View
                        </a>
                        @endif
                        <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Delete this post?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100 transition-colors">
                                🗑 Delete
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop: table --}}
        <div class="hidden sm:block bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Title (EN)</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Category</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Translations</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($posts as $post)
                        @php
                            $enTitle = $post->translation('en')?->title ?? '—';
                            $hasEn   = $post->translation('en') !== null;
                            $hasBn   = $post->translation('bn') !== null;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900 max-w-xs truncate">{{ $enTitle }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $post->category->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1">
                                    <span class="rounded px-1.5 py-0.5 text-xs font-medium {{ $hasEn ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">EN</span>
                                    <span class="rounded px-1.5 py-0.5 text-xs font-medium {{ $hasBn ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">BN</span>
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                @if($post->status === 'published')
                                    <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Published</span>
                                @else
                                    <span class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700">Draft</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $post->created_at->format('M d, Y') }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.posts.edit', $post) }}"
                                       class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 transition-colors whitespace-nowrap">
                                        ✏️ Edit
                                    </a>
                                    @if($post->status === 'published')
                                    <a href="{{ route('blog.show', ['locale' => 'en', 'slug' => $post->slug]) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-100 transition-colors whitespace-nowrap">
                                        👁 View
                                    </a>
                                    @endif
                                    <form action="{{ route('admin.posts.destroy', $post) }}" method="POST"
                                          class="inline" onsubmit="return confirm('Delete this post?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100 transition-colors whitespace-nowrap">
                                            🗑 Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $posts->links() }}</div>
    @endif

@endsection

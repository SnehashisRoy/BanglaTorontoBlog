@extends('layouts.blog')

@section('title', 'New Post')

@section('content')

    <div class="mb-6">
        <a href="{{ route('admin.posts.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Back to posts</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-8 max-w-2xl">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">New Post</h1>

        @if($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.posts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                <input type="file" id="image" name="image" accept="image/*"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <p class="mt-1 text-xs text-gray-400">JPG, PNG or WEBP, up to 2MB.</p>
            </div>

            {{-- Shared fields --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select id="category_id" name="category_id" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">— Select —</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">
                    Slug <span class="text-gray-400 font-normal">(auto-generated from English title if blank)</span>
                </label>
                <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>

            <hr class="border-gray-200">

            {{-- English translation --}}
            <div class="space-y-4">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">English (EN)</h2>
                <div>
                    <label for="title_en" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" id="title_en" name="title_en" value="{{ old('title_en') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Body</label>
                    <div id="editor_en" class="rounded-lg border border-gray-300 bg-white" style="min-height:200px"></div>
                    <textarea id="body_en" name="body_en" class="hidden">{{ old('body_en') }}</textarea>
                </div>
            </div>

            <hr class="border-gray-200">

            {{-- Bengali translation --}}
            <div class="space-y-4">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Bengali / বাংলা (BN)</h2>
                <div>
                    <label for="title_bn" class="block text-sm font-medium text-gray-700 mb-1">শিরোনাম (Title)</label>
                    <input type="text" id="title_bn" name="title_bn" value="{{ old('title_bn') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">বিষয়বস্তু (Body)</label>
                    <div id="editor_bn" class="rounded-lg border border-gray-300 bg-white" style="min-height:200px"></div>
                    <textarea id="body_bn" name="body_bn" class="hidden">{{ old('body_bn') }}</textarea>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition-colors text-center">
                    Create Post
                </button>
                <a href="{{ route('admin.posts.index') }}"
                   class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>

@endsection

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script>
    const toolbar = [
        ['bold', 'italic', 'underline'],
        ['blockquote'],
        [{ header: [1, 2, 3, false] }],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link'],
        ['clean'],
    ];

    const quillEn = new Quill('#editor_en', { theme: 'snow', modules: { toolbar } });
    const quillBn = new Quill('#editor_bn', { theme: 'snow', modules: { toolbar } });

    quillEn.root.innerHTML = document.getElementById('body_en').value;
    quillBn.root.innerHTML = document.getElementById('body_bn').value;

    document.querySelector('form').addEventListener('submit', function () {
        document.getElementById('body_en').value = quillEn.root.innerHTML;
        document.getElementById('body_bn').value = quillBn.root.innerHTML;
    });
</script>
@endpush

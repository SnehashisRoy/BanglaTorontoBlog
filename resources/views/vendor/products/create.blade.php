@extends('layouts.vendor')

@section('title', 'New Product')

@section('content')

    <div class="mb-6">
        <a href="{{ route('vendor.products.index') }}" class="text-sm text-[#27ae60] hover:text-[#1a7a44]">&larr; Back to products</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-8 max-w-2xl">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">New Product</h1>

        @if($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('vendor.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label for="images" class="block text-sm font-medium text-gray-700 mb-1">Photos</label>
                <input type="file" id="images" name="images[]" accept="image/*" multiple
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                <p class="mt-1 text-xs text-gray-400">JPG, PNG or WEBP, up to 2MB each, up to 8 photos. First photo is the cover image.</p>
                <p id="ai-status" class="mt-2 text-xs text-gray-500 hidden"></p>
                <button type="button" id="ai-regenerate"
                        class="hidden mt-1 inline-flex items-center gap-1 text-xs font-medium text-[#27ae60] hover:text-[#1a7a44] hover:underline">
                    ↻ Regenerate title &amp; description with AI
                </button>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="product_category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select id="product_category_id" name="product_category_id"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                        <option value="">— None —</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('product_category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price <span class="text-gray-400 font-normal">(leave blank for "contact for price")</span></label>
                    <input type="number" id="price" name="price" value="{{ old('price') }}" step="0.01" min="0"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                </div>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">
                    <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                </select>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="5"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#27ae60] focus:outline-none focus:ring-1 focus:ring-[#27ae60]">{{ old('description') }}</textarea>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-[#27ae60] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#1a7a44] transition-colors text-center">
                    Create Product
                </button>
                <a href="{{ route('vendor.products.index') }}"
                   class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    const imagesInput = document.getElementById('images');
    const nameInput = document.getElementById('name');
    const descriptionInput = document.getElementById('description');
    const categorySelect = document.getElementById('product_category_id');
    const aiStatus = document.getElementById('ai-status');
    const aiRegenerate = document.getElementById('ai-regenerate');

    let lastFile = null;

    function setStatus(text) {
        aiStatus.textContent = text;
        aiStatus.classList.remove('hidden');
    }

    async function requestSuggestion(file) {
        setStatus('✨ Generating title & description from your photo…');
        aiRegenerate.disabled = true;

        const formData = new FormData();
        formData.append('image', file);
        if (categorySelect.value) {
            formData.append('product_category_id', categorySelect.value);
        }

        try {
            const response = await fetch('{{ route('vendor.products.suggest-description') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    Accept: 'application/json',
                },
                body: formData,
            });

            if (!response.ok) {
                throw new Error('request failed');
            }

            const data = await response.json();
            nameInput.value = data.name;
            descriptionInput.value = data.description;
            setStatus('✨ Suggested by AI from your photo — feel free to edit before publishing.');
            aiRegenerate.classList.remove('hidden');
        } catch (error) {
            setStatus('Could not generate a suggestion for this photo. You can fill this in yourself.');
        } finally {
            aiRegenerate.disabled = false;
        }
    }

    imagesInput.addEventListener('change', function () {
        if (imagesInput.files.length === 0) {
            return;
        }

        lastFile = imagesInput.files[0];

        // Only auto-fill empty fields so we never clobber what the vendor already typed.
        if (!nameInput.value.trim() && !descriptionInput.value.trim()) {
            requestSuggestion(lastFile);
        } else {
            aiRegenerate.classList.remove('hidden');
        }
    });

    aiRegenerate.addEventListener('click', function () {
        if (lastFile) {
            requestSuggestion(lastFile);
        }
    });
})();
</script>
@endpush

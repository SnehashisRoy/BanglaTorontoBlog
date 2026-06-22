<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::with('translations', 'category')->latest()->paginate(15);

        return view('admin.posts.index', compact('posts'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.posts.create', compact('categories'));
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $fallbackTitle = $data['title_en'] ?? $data['title_bn'];

        $post = Post::create([
            'category_id' => $data['category_id'],
            'slug'        => Str::slug($data['slug'] ?: $fallbackTitle),
            'status'      => $data['status'],
            'image_path'  => $request->file('image')?->store('posts', 'public'),
        ]);

        foreach (['en', 'bn'] as $locale) {
            if (! empty($data["title_{$locale}"])) {
                $post->translations()->create([
                    'language' => $locale,
                    'title'    => $data["title_{$locale}"],
                    'body'     => $data["body_{$locale}"],
                ]);
            }
        }

        return redirect()->route('admin.posts.index')->with('success', 'Post created.');
    }

    public function edit(Post $post): View
    {
        $post->load('translations');
        $categories  = Category::orderBy('name')->get();
        $enTranslation = $post->translation('en');
        $bnTranslation = $post->translation('bn');

        return view('admin.posts.edit', compact('post', 'categories', 'enTranslation', 'bnTranslation'));
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->validated();

        $fallbackTitle = $data['title_en'] ?? $data['title_bn'];

        $imagePath = $post->image_path;

        if ($request->file('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('posts', 'public');
        } elseif ($request->boolean('remove_image') && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        $post->update([
            'category_id' => $data['category_id'],
            'slug'        => Str::slug($data['slug'] ?: $fallbackTitle),
            'status'      => $data['status'],
            'image_path'  => $imagePath,
        ]);

        foreach (['en', 'bn'] as $locale) {
            if (! empty($data["title_{$locale}"])) {
                $post->translations()->updateOrCreate(
                    ['language' => $locale],
                    ['title' => $data["title_{$locale}"], 'body' => $data["body_{$locale}"]],
                );
            } else {
                $post->translations()->where('language', $locale)->delete();
            }
        }

        return redirect()->route('admin.posts.index')->with('success', 'Post updated.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Post deleted.');
    }
}

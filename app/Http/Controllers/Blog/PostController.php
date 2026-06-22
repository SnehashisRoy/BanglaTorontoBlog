<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Repositories\PostRepository;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(private readonly PostRepository $posts) {}

    public function index(string $locale): View
    {
        $posts = $this->posts->publishedByLocale(app()->getLocale());

        return view('blog.index', compact('posts') + ['showLocaleSwitch' => true]);
    }

    public function show(string $locale, string $slug): View
    {
        $post = $this->posts->findPublishedBySlug($slug, app()->getLocale());

        return view('blog.show', compact('post') + ['showLocaleSwitch' => true, 'switcherSlug' => $slug]);
    }
}

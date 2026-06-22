<?php

namespace App\Repositories;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

class PostRepository
{
    public function createWithTranslations(
        array $postData,
        array $enTranslation,
        array $bnTranslation,
    ): Post {
        $post = Post::create($postData);

        $post->translations()->createMany([
            ['language' => 'en', 'title' => $enTranslation['title'], 'body' => $enTranslation['body']],
            ['language' => 'bn', 'title' => $bnTranslation['title'], 'body' => $bnTranslation['body']],
        ]);

        return $post->load('translations');
    }


    public function publishedByLocale(string $locale, int $perPage = 10): LengthAwarePaginator
    {
        return Post::with([
                'category',
                'translations' => fn ($q) => $q->where('language', $locale),
            ])
            ->where('status', 'published')
            ->hasTranslation($locale)
            ->latest()
            ->paginate($perPage);
    }

    public function findPublishedBySlug(string $slug, string $locale): Post
    {
        return Post::with([
                'category',
                'translations' => fn ($q) => $q->where('language', $locale),
            ])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->hasTranslation($locale)
            ->firstOrFail();
    }
}

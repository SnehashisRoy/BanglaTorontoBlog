<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing posts (translations cascade-delete)
        Post::query()->delete();

        $tech = Category::where('slug', 'technology')->first();

        $post = Post::create([
            'category_id' => $tech->id,
            'slug'        => 'getting-started-with-laravel',
            'status'      => 'published',
        ]);

        $post->translations()->createMany([
            [
                'language' => 'en',
                'title'    => 'Getting Started with Laravel',
                'body'     => <<<EOT
Laravel is a web application framework with expressive, elegant syntax. It attempts to take the pain out of development by easing common tasks used in most web projects.

Laravel makes it easy to build modern PHP applications. With built-in routing, migrations, Eloquent ORM, and a powerful templating engine, you can go from idea to production faster than ever.

In this post we cover the basics of setting up a new Laravel project, connecting a database, and building your first route and view. By the end you will have a solid foundation to build any application you can imagine.
EOT,
            ],
            [
                'language' => 'bn',
                'title'    => 'Laravel দিয়ে শুরু করুন',
                'body'     => <<<EOT
Laravel একটি শক্তিশালী PHP ফ্রেমওয়ার্ক যা দিয়ে সহজেই ওয়েব অ্যাপ্লিকেশন তৈরি করা যায়। এটি ডেভেলপমেন্টকে সহজ ও আনন্দদায়ক করে তোলে।

Laravel-এর বিল্ট-ইন রাউটিং, মাইগ্রেশন, Eloquent ORM এবং Blade টেমপ্লেট ইঞ্জিন ব্যবহার করে আপনি দ্রুত যেকোনো অ্যাপ্লিকেশন তৈরি করতে পারবেন।

এই পোস্টে আমরা একটি নতুন Laravel প্রজেক্ট সেটআপ, ডাটাবেজ সংযোগ এবং প্রথম রাউট ও ভিউ তৈরির মূল বিষয়গুলো দেখব।
EOT,
            ],
        ]);
    }
}

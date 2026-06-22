<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Move translatable data only if post_translations is still empty
        if (DB::table('post_translations')->count() === 0 && Schema::hasColumn('posts', 'title')) {
            DB::table('posts')->get()->each(function (object $post) {
                DB::table('post_translations')->insert([
                    'post_id'    => $post->id,
                    'language'   => $post->language ?? 'en',
                    'title'      => $post->title,
                    'body'       => $post->body,
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                ]);
            });
        }

        // Drop title / body / language only if they still exist
        $toDrop = array_values(array_filter(
            ['title', 'body', 'language'],
            fn (string $col) => Schema::hasColumn('posts', $col),
        ));

        if (! empty($toDrop)) {
            Schema::table('posts', function (Blueprint $table) use ($toDrop) {
                $table->dropColumn($toDrop);
            });
        }

        // Restore slug if a previous failed run dropped it
        if (! Schema::hasColumn('posts', 'slug')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('category_id');
            });

            // Populate from EN translation title (BN as fallback)
            DB::table('posts')->get()->each(function (object $post) {
                $t = DB::table('post_translations')
                    ->where('post_id', $post->id)
                    ->orderByRaw("FIELD(language,'en','bn')")
                    ->first();

                DB::table('posts')->where('id', $post->id)->update([
                    'slug' => Str::slug(($t?->title ?? '') ?: 'post-' . $post->id),
                ]);
            });

            Schema::table('posts', function (Blueprint $table) {
                $table->string('slug')->nullable(false)->change();
                $table->unique('slug');
            });
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique('posts_slug_unique');
            $table->dropColumn('slug');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('title')->after('category_id');
            $table->string('slug')->unique()->after('title');
            $table->longText('body')->after('slug');
            $table->enum('language', ['en', 'bn'])->default('en')->after('body');
        });

        DB::table('post_translations')->get()->each(function (object $t) {
            DB::table('posts')->where('id', $t->post_id)->update([
                'title'    => $t->title,
                'body'     => $t->body,
                'language' => $t->language,
            ]);
        });

        DB::table('post_translations')->truncate();
    }
};

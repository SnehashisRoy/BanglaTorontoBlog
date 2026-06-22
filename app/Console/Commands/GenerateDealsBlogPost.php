<?php

namespace App\Console\Commands;

use Anthropic\Client;
use App\Models\Category;
use App\Repositories\PostRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateDealsBlogPost extends Command
{
    protected $signature = 'deals:blog
                            {--store= : Only generate for a specific store name (partial match)}';

    protected $description = 'Generate a bilingual (EN+BN) blog post per store from deals relevant to the Bengali community';

    private const STORES = [
        ['name' => 'No Frills',                 'url' => 'https://www.nofrills.ca/en/deals/flyer'],
        ['name' => 'FreshCo',                   'url' => 'https://www.freshco.com/en/deals/flyer'],
        ['name' => 'Metro',                     'url' => 'https://www.metro.ca/en/flyer'],
        ['name' => 'Food Basics',               'url' => 'https://www.foodbasics.ca/en/deals/flyer'],
        ['name' => 'Real Canadian Superstore',  'url' => 'https://www.realcanadiansuperstore.ca/en/deals/flyer'],
        ['name' => 'Al Premium',                'url' => 'https://alpremium.ca/pages/weekly-special-eglinton'],
    ];

    private const KEYWORDS = [
        'rice', 'basmati', 'jasmine', 'lentil', 'dal', 'dhal', 'masoor', 'chana', 'chickpea',
        'fish', 'salmon', 'tilapia', 'shrimp', 'prawn', 'cod', 'catfish', 'hilsa', 'rohu', 'trout',
        'mustard', 'eggplant', 'brinjal', 'aubergine', 'okra', 'bitter gourd', 'karela',
        'chicken', 'goat', 'lamb', 'turmeric', 'ginger', 'garlic', 'onion', 'potato',
        'spinach', 'cabbage', 'cauliflower', 'pumpkin', 'squash', 'coconut', 'mango',
        'banana', 'cilantro', 'coriander', 'cumin', 'cardamom', 'oil', 'canola',
        'yogurt', 'paneer', 'ghee', 'flour', 'atta', 'milk', 'bread', 'egg',
    ];

    public function handle(PostRepository $posts): int
    {
        $stores = $this->selectStores();
        $client = new Client(apiKey: config('services.anthropic.key'));
        $category = Category::firstOrCreate(['slug' => 'deals'], ['name' => 'deals']);
        $created = 0;

        foreach ($stores as $store) {
            $this->line('');
            $this->info("=== {$store['name']} ===");

            $deals = $this->fetchRelevantDeals($store['url']);

            if ($deals->isEmpty()) {
                $this->warn("  No relevant deals found — skipping.");
                continue;
            }

            $this->line("  Found {$deals->count()} relevant deals. Generating content...");

            try {
                [$en, $bn] = $this->generateContent($client, $store['name'], $deals);
            } catch (\Throwable $e) {
                $this->error("  Claude error: {$e->getMessage()}");
                continue;
            }

            $slug = $this->uniqueSlug(Str::slug($en['title']));

            $post = $posts->createWithTranslations(
                postData: ['category_id' => $category->id, 'slug' => $slug, 'status' => 'published'],
                enTranslation: $en,
                bnTranslation: $bn,
            );

            $this->line("  ✓ Post created (id={$post->id}, slug={$post->slug})");
            $this->line("    EN: {$en['title']}");
            $this->line("    BN: {$bn['title']}");
            $created++;
        }

        $this->line('');
        $this->info("Done. Created {$created}/" . count($stores) . " blog posts.");

        return $created > 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function selectStores(): array
    {
        $filter = $this->option('store');

        if (! $filter) {
            return self::STORES;
        }

        $matches = array_values(array_filter(
            self::STORES,
            fn ($s) => str_contains(strtolower($s['name']), strtolower($filter))
        ));

        if (empty($matches)) {
            $this->error("No store matched '{$filter}'. Available: " .
                implode(', ', array_column(self::STORES, 'name')));
        }

        return $matches;
    }

    private function fetchRelevantDeals(string $storeUrl): Collection
    {
        return DB::table('deals')
            ->where('url', $storeUrl)
            ->whereNotNull('price')
            ->where(function ($q): void {
                foreach (self::KEYWORDS as $keyword) {
                    $q->orWhere('title', 'like', "%{$keyword}%");
                }
            })
            ->orderBy('title')
            ->get();
    }

    private function generateContent(Client $client, string $storeName, Collection $deals): array
    {
        $dealLines = $deals->map(fn ($d) => "- {$d->title}: \${$d->price}" .
            ($d->valid_to ? " (valid until {$d->valid_to})" : '')
        )->implode("\n");

        $prompt = <<<PROMPT
            You are a deal-alert writer for BanglaToronto, a website for the Bengali-speaking community in Toronto, Canada.

            Below are this week's grocery deals at {$storeName} that are relevant to Bengali households:

            {$dealLines}

            Write a deal-alert blog post in TWO languages: English and Bengali (বাংলা).

            Focus ONLY on the prices — do NOT suggest any recipes or cooking instructions.
            Highlight which items are on sale, their current prices, and why these are worth picking up this week.

            Respond ONLY with a valid JSON object in exactly this format (no markdown, no extra text):
            {
              "en": {
                "title": "English title here",
                "body": "<p>English body HTML here...</p>"
              },
              "bn": {
                "title": "Bengali title here",
                "body": "<p>Bengali body HTML here...</p>"
              }
            }

            Guidelines:
            - English post: 200-300 words. List the deals with prices clearly using <ul><li> tags.
            - Bengali post: natural translation/adaptation in standard Bengali (বাংলা), 200-300 words.
            - Use <p>, <ul>, <li>, <strong> tags in the body.
            - Include the store name and the valid dates.
            - No recipe suggestions, no cooking tips — prices and savings only.
            - Do NOT include markdown code fences or any text outside the JSON object.
            PROMPT;

        $response = $client->messages->create(
            model: 'claude-opus-4-8',
            maxTokens: 3000,
            thinking: ['type' => 'adaptive'],
            messages: [['role' => 'user', 'content' => $prompt]],
        );

        $jsonText = '';
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $jsonText = $block->text;
                break;
            }
        }

        $jsonText = preg_replace('/^```[a-z]*\s*/i', '', trim($jsonText));
        $jsonText = preg_replace('/\s*```$/', '', $jsonText);

        $data = json_decode($jsonText, true);

        if (! $data || ! isset($data['en']['title'], $data['en']['body'], $data['bn']['title'], $data['bn']['body'])) {
            throw new \RuntimeException("Unexpected Claude response: {$jsonText}");
        }

        return [
            ['title' => $data['en']['title'], 'body' => $data['en']['body']],
            ['title' => $data['bn']['title'], 'body' => $data['bn']['body']],
        ];
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base ?: 'deals-post';
        $suffix = 1;

        while (DB::table('posts')->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}

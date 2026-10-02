<?php

namespace App\Console\Commands;

use App\Models\ProductCategory;
use App\Models\Vendor;
use App\Repositories\ProductRepository;
use App\Services\ProductSuggestionService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class ImportProductsFromImages extends Command
{
    protected $signature = 'products:import-from-images
                            {vendor : Vendor ID or slug to attach the created products to}
                            {directory : Path to a folder containing one photo per product}
                            {--category= : Product category slug to assign to every created product}
                            {--status=draft : Status for created products (draft or published)}
                            {--dry-run : Ask Claude for a title/description preview without creating anything}';

    protected $description = 'Create one product per image in a folder, using Claude to write the title and description from each photo';

    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function handle(ProductRepository $products, ProductSuggestionService $suggestions): int
    {
        $vendor = $this->resolveVendor();

        if (! $vendor) {
            return Command::FAILURE;
        }

        $status = $this->option('status');

        if (! in_array($status, ['draft', 'published'], true)) {
            $this->error("Invalid --status '{$status}'. Use 'draft' or 'published'.");

            return Command::FAILURE;
        }

        $category = null;

        if ($categorySlug = $this->option('category')) {
            $category = ProductCategory::where('slug', $categorySlug)->first();

            if (! $category) {
                $this->warn("No product category found for slug '{$categorySlug}' — continuing without a category.");
            }
        }

        $files = $this->resolveImageFiles($this->argument('directory'));

        if ($files === null) {
            return Command::FAILURE;
        }

        if (empty($files)) {
            $this->warn('No supported images (jpg, jpeg, png, webp) found in that folder.');

            return Command::FAILURE;
        }

        $dryRun = $this->option('dry-run');

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Importing {$this->countLabel(count($files))} for \"{$vendor->name}\"".
            ($category ? " into \"{$category->name}\"" : '').'...');
        $this->line('');

        $succeeded = 0;
        $failed = 0;

        foreach ($files as $path) {
            $filename = basename($path);
            $this->line("→ {$filename}");

            $uploaded = new UploadedFile($path, $filename, mime_content_type($path) ?: null, null, true);

            try {
                $suggestion = $suggestions->suggest($uploaded, $category?->name);
            } catch (\Throwable $e) {
                $this->error("  Claude error: {$e->getMessage()} — skipping.");
                $failed++;

                continue;
            }

            $this->line("  Title: {$suggestion->name}");

            if ($dryRun) {
                $this->line('  (dry run — nothing created)');
                $this->line('');
                $succeeded++;

                continue;
            }

            $product = $products->createForVendor($vendor, [
                'name' => $suggestion->name,
                'description' => $suggestion->description,
                'product_category_id' => $category?->id,
                'status' => $status,
            ]);

            $products->syncImages($product, [$uploaded]);

            $this->line("  ✓ Created product #{$product->id} ({$status})");
            $this->line('');

            $succeeded++;
        }

        $this->info(($dryRun
            ? "Done. Previewed {$succeeded}/".count($files)
            : "Done. Created {$succeeded}/".count($files).' products').
            ($failed ? ". {$failed} failed." : '.'));

        return $succeeded > 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function resolveVendor(): ?Vendor
    {
        $identifier = $this->argument('vendor');

        $vendor = ctype_digit((string) $identifier)
            ? Vendor::find((int) $identifier)
            : Vendor::where('slug', $identifier)->first();

        if (! $vendor) {
            $this->error("No vendor found for '{$identifier}' (tried as ID and as slug).");

            return null;
        }

        return $vendor;
    }

    /**
     * @return array<int, string>|null absolute file paths, or null on error
     */
    private function resolveImageFiles(string $directory): ?array
    {
        $resolved = realpath($directory) ?: realpath(base_path($directory));

        if (! $resolved || ! is_dir($resolved)) {
            $this->error("Directory not found: {$directory}");

            return null;
        }

        $paths = array_map(
            fn ($file) => $file->getPathname(),
            File::files($resolved),
        );

        $paths = array_values(array_filter(
            $paths,
            fn ($path) => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::SUPPORTED_EXTENSIONS, true),
        ));

        sort($paths);

        return $paths;
    }

    private function countLabel(int $count): string
    {
        return $count === 1 ? '1 product' : "{$count} products";
    }
}

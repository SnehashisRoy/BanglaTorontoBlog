<?php

namespace Tests\Feature\Console;

use App\Dto\ProductSuggestion;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Vendor;
use App\Services\ProductSuggestionService;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportProductsFromImagesTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/product-import-test-'.uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*') ?: []);
        rmdir($this->dir);

        parent::tearDown();
    }

    /**
     * A minimal but genuinely valid 1x1 PNG, written to disk under the given name.
     */
    private function putFakePhoto(string $filename): string
    {
        $onePixelPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        );

        $path = $this->dir.'/'.$filename;
        file_put_contents($path, $onePixelPng);

        return $path;
    }

    /**
     * @param  array<ProductSuggestion>  $suggestionsByCallOrder
     */
    private function mockSuggestionsReturning(array $suggestionsByCallOrder): void
    {
        $this->mock(ProductSuggestionService::class, function ($mock) use ($suggestionsByCallOrder) {
            $mock->shouldReceive('suggest')
                ->times(count($suggestionsByCallOrder))
                ->andReturn(...$suggestionsByCallOrder);
        });
    }

    public function test_it_creates_one_product_per_image_with_ai_generated_content(): void
    {
        Storage::fake('public');

        $vendor = Vendor::factory()->create();
        $this->putFakePhoto('a.png');
        $this->putFakePhoto('b.png');

        $this->mockSuggestionsReturning([
            new ProductSuggestion(name: 'Handwoven Shawl', description: 'A soft, hand-stitched shawl.'),
            new ProductSuggestion(name: 'Basmati Rice 10kg', description: 'Premium aged basmati rice.'),
        ]);

        $this->artisan('products:import-from-images', [
            'vendor' => $vendor->slug,
            'directory' => $this->dir,
            '--status' => 'published',
        ])->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', [
            'vendor_id' => $vendor->id,
            'name' => 'Handwoven Shawl',
            'description' => 'A soft, hand-stitched shawl.',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('products', [
            'vendor_id' => $vendor->id,
            'name' => 'Basmati Rice 10kg',
            'status' => 'published',
        ]);

        $product = Product::where('name', 'Handwoven Shawl')->first();
        $this->assertCount(1, $product->images);
        Storage::disk('public')->assertExists($product->images->first()->path);
    }

    public function test_dry_run_previews_without_creating_anything(): void
    {
        $vendor = Vendor::factory()->create();
        $this->putFakePhoto('a.png');

        $this->mockSuggestionsReturning([
            new ProductSuggestion(name: 'Preview Title', description: 'Preview description.'),
        ]);

        $this->artisan('products:import-from-images', [
            'vendor' => $vendor->slug,
            'directory' => $this->dir,
            '--dry-run' => true,
        ])->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_assigns_the_requested_category(): void
    {
        $vendor = Vendor::factory()->create();
        $category = ProductCategory::factory()->create(['slug' => 'groceries']);
        $this->putFakePhoto('a.png');

        $this->mockSuggestionsReturning([
            new ProductSuggestion(name: 'Rice', description: 'Nice rice.'),
        ]);

        $this->artisan('products:import-from-images', [
            'vendor' => $vendor->slug,
            'directory' => $this->dir,
            '--category' => 'groceries',
        ])->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('products', [
            'name' => 'Rice',
            'product_category_id' => $category->id,
        ]);
    }

    public function test_unknown_vendor_fails_gracefully(): void
    {
        $this->putFakePhoto('a.png');

        $this->artisan('products:import-from-images', [
            'vendor' => 'no-such-vendor',
            'directory' => $this->dir,
        ])->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_invalid_status_option_fails_gracefully(): void
    {
        $vendor = Vendor::factory()->create();
        $this->putFakePhoto('a.png');

        $this->artisan('products:import-from-images', [
            'vendor' => $vendor->slug,
            'directory' => $this->dir,
            '--status' => 'archived',
        ])->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_empty_folder_fails_gracefully(): void
    {
        $vendor = Vendor::factory()->create();

        $this->artisan('products:import-from-images', [
            'vendor' => $vendor->slug,
            'directory' => $this->dir,
        ])->assertExitCode(Command::FAILURE);
    }

    public function test_a_failed_suggestion_is_skipped_without_aborting_the_batch(): void
    {
        Storage::fake('public');

        $vendor = Vendor::factory()->create();
        $this->putFakePhoto('a.png');
        $this->putFakePhoto('b.png');

        $this->mock(ProductSuggestionService::class, function ($mock) {
            $call = 0;

            $mock->shouldReceive('suggest')
                ->twice()
                ->andReturnUsing(function () use (&$call) {
                    $call++;

                    if ($call === 1) {
                        throw new \RuntimeException('boom');
                    }

                    return new ProductSuggestion(name: 'Second Photo', description: 'Worked this time.');
                });
        });

        $this->artisan('products:import-from-images', [
            'vendor' => $vendor->slug,
            'directory' => $this->dir,
        ])->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['name' => 'Second Photo']);
    }
}

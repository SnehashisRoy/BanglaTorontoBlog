<?php

namespace Tests\Feature\Vendor;

use App\Dto\ProductSuggestion;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ProductSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductSuggestionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A minimal but genuinely valid 1x1 PNG — built without the GD extension
     * (which isn't installed here) so Laravel's real `image`/`mimes` rules pass.
     */
    private function fakePhoto(string $name = 'product.png'): UploadedFile
    {
        $onePixelPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        );

        return UploadedFile::fake()->createWithContent($name, $onePixelPng);
    }

    public function test_guest_cannot_request_a_suggestion(): void
    {
        $response = $this->postJson(route('vendor.products.suggest-description'), [
            'image' => $this->fakePhoto(),
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_non_vendor_cannot_request_a_suggestion(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('vendor.products.suggest-description'), [
            'image' => $this->fakePhoto(),
        ]);

        $response->assertForbidden();
    }

    public function test_image_is_required(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();

        $response = $this->actingAs($user)->postJson(route('vendor.products.suggest-description'), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('image');
    }

    public function test_vendor_can_get_an_ai_suggestion_from_an_uploaded_photo(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();

        $suggestion = new ProductSuggestion(
            name: 'Handwoven Nakshi Kantha Shawl',
            description: 'A soft, hand-stitched shawl with traditional nakshi embroidery.',
        );

        $this->mock(ProductSuggestionService::class, function ($mock) use ($suggestion) {
            $mock->shouldReceive('suggest')->once()->andReturn($suggestion);
        });

        $response = $this->actingAs($user)->postJson(route('vendor.products.suggest-description'), [
            'image' => $this->fakePhoto(),
        ]);

        $response->assertOk();
        $response->assertJson([
            'name' => 'Handwoven Nakshi Kantha Shawl',
            'description' => 'A soft, hand-stitched shawl with traditional nakshi embroidery.',
        ]);
    }

    public function test_a_service_failure_returns_a_friendly_error_instead_of_crashing(): void
    {
        $user = User::factory()->has(Vendor::factory())->create();

        $this->mock(ProductSuggestionService::class, function ($mock) {
            $mock->shouldReceive('suggest')->once()->andThrow(new \RuntimeException('boom'));
        });

        $response = $this->actingAs($user)->postJson(route('vendor.products.suggest-description'), [
            'image' => $this->fakePhoto(),
        ]);

        $response->assertStatus(502);
        $response->assertJsonStructure(['message']);
    }
}

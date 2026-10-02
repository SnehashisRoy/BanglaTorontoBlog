<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Messages\TextBlock;
use App\Dto\ProductSuggestion;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class ProductSuggestionService
{
    private const MEDIA_TYPES = [
        'image/jpeg' => 'image/jpeg',
        'image/png' => 'image/png',
        'image/webp' => 'image/webp',
    ];

    public function __construct(private readonly ?Client $client) {}

    /**
     * Ask Claude to suggest a product title and description from a photo.
     */
    public function suggest(UploadedFile $image, ?string $categoryName = null): ProductSuggestion
    {
        if (! $this->client) {
            throw new RuntimeException('AI suggestions are not configured (missing ANTHROPIC_API_KEY).');
        }

        $mediaType = self::MEDIA_TYPES[$image->getMimeType()] ?? null;

        if (! $mediaType) {
            throw new RuntimeException('Unsupported image type for AI suggestions.');
        }

        $data = base64_encode((string) file_get_contents($image->getRealPath()));

        $categoryHint = $categoryName
            ? "It will be listed in the \"{$categoryName}\" category."
            : '';

        $message = $this->client->messages->create(
            model: 'claude-sonnet-5',
            maxTokens: 1024,
            outputConfig: [
                'effort' => 'low',
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'description' => 'A short, specific, customer-facing product title (max ~70 characters). No marketing fluff, no emoji, no quotes.',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'A clear 2-4 sentence product description a buyer would find useful: what it is, material/contents, condition or key features. Plain text, no markdown.',
                            ],
                        ],
                        'required' => ['name', 'description'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            messages: [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'mediaType' => $mediaType,
                                'data' => $data,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => "This photo was just uploaded by a vendor creating a product listing on a local marketplace. {$categoryHint} Suggest a title and description for this product based only on what is visible in the photo. If you genuinely cannot tell what the product is, use a generic but honest title (e.g. \"Handmade Item\") rather than guessing specifics.",
                        ],
                    ],
                ],
            ],
        );

        $json = null;

        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                $json = json_decode($block->text, true);

                break;
            }
        }

        if (! is_array($json) || ! isset($json['name'], $json['description'])) {
            throw new RuntimeException('Claude did not return a usable product suggestion.');
        }

        return new ProductSuggestion(
            name: (string) $json['name'],
            description: (string) $json['description'],
        );
    }
}

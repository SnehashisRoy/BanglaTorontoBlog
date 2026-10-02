<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Services\ProductSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ProductSuggestionController extends Controller
{
    public function __construct(private readonly ProductSuggestionService $suggestions) {}

    /**
     * This is a fetch()-driven AJAX endpoint, but the app's global
     * `shouldRenderJsonWhen` only auto-renders exceptions as JSON for
     * `api/*` routes — so validation is handled explicitly here instead
     * of via `$request->validate()`, to guarantee a JSON response.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $categoryName = isset($data['product_category_id'])
            ? ProductCategory::where('id', $data['product_category_id'])->first()?->name
            : null;

        try {
            $suggestion = $this->suggestions->suggest($request->file('image'), $categoryName);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Could not generate a suggestion right now. Please fill this in yourself.',
            ], 502);
        }

        return response()->json([
            'name' => $suggestion->name,
            'description' => $suggestion->description,
        ]);
    }
}

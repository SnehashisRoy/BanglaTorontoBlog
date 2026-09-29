<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $this->user()?->vendor !== null
            && $product instanceof Product
            && $product->vendor_id === $this->user()->vendor->id;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:191'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'status' => ['required', 'in:draft,published'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'delete_image_ids' => ['nullable', 'array'],
            'delete_image_ids.*' => ['integer', 'exists:product_images,id'],
        ];
    }
}

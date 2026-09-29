<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vendor !== null;
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
        ];
    }
}

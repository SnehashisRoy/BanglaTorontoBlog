<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        $postId = $this->route('post')?->id;

        return [
            'slug'        => ['nullable', 'string', 'max:255', "unique:posts,slug,{$postId}"],
            'category_id' => ['required', 'exists:categories,id'],
            'status'      => ['required', 'in:draft,published'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'title_en'    => ['nullable', 'string', 'max:255', 'required_without:title_bn'],
            'body_en'     => ['nullable', 'string', 'required_with:title_en'],
            'title_bn'    => ['nullable', 'string', 'max:255', 'required_without:title_en'],
            'body_bn'     => ['nullable', 'string', 'required_with:title_bn'],
        ];
    }

    public function messages(): array
    {
        return [
            'title_en.required_without' => 'At least one language (English or Bengali) must have a title and body.',
            'title_bn.required_without' => 'At least one language (English or Bengali) must have a title and body.',
        ];
    }
}

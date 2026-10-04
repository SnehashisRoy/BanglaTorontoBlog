<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCjMarkupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vendor?->cj_dropshipping_enabled ?? false;
    }

    public function rules(): array
    {
        return [
            'cj_markup_percent' => ['required', 'numeric', 'min:0', 'max:500'],
        ];
    }
}

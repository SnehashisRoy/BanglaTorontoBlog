<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConnectCjRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vendor?->cj_dropshipping_enabled ?? false;
    }

    public function rules(): array
    {
        return [
            'api_key' => ['required', 'string', 'max:200'],
        ];
    }
}

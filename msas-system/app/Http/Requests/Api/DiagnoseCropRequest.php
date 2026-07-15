<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DiagnoseCropRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'image'     => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'crop_type' => ['sometimes', 'string', 'max:100'],
            'symptoms'  => ['sometimes', 'string', 'max:1000'],
            'location'  => ['sometimes', 'string', 'max:200'],
        ];
    }
}

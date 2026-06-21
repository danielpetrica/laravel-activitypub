<?php

namespace DanielPetrica\LaravelActivityPub\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'icon_url' => ['nullable', 'string', 'url', 'max:2048'],
            'image_url' => ['nullable', 'string', 'url', 'max:2048'],
        ];
    }
}

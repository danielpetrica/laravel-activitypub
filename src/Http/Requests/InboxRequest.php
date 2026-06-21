<?php

namespace DanielPetrica\LaravelActivityPub\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string'],
            'actor' => ['required', 'string', 'url'],
            'object' => ['required'],
            '@context' => ['present'],
            'id' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Activity type is required.',
            'actor.required' => 'Activity actor is required.',
            'actor.url' => 'Activity actor must be a valid URL.',
            'object.required' => 'Activity object is required.',
            '@context.present' => 'Activity @context must be present.',
        ];
    }
}

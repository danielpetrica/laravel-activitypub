<?php

namespace DanielPetrica\LaravelActivityPub\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebFingerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resource' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'resource.required' => 'The "resource" parameter is required.',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $resource = $this->query('resource');

            if ($resource === null) {
                return;
            }

            if (! str_starts_with($resource, 'acct:')) {
                $validator->errors()->add('resource', 'Only "acct:" URI scheme is supported.');
                return;
            }

            $account = substr($resource, 5);
            $parts = explode('@', $account);

            if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                $validator->errors()->add('resource', 'Invalid acct URI format. Expected acct:user@domain.');
            }
        });
    }
}

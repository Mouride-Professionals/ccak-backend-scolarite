<?php

declare(strict_types=1);

namespace App\Http\Requests\Templates;

use Illuminate\Foundation\Http\FormRequest;

class PreviewEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string'],
            'path' => ['nullable', 'string'],
            'variables' => ['nullable', 'array'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if (!$this->filled('name') && !$this->filled('path')) {
                $validator->errors()->add('name', 'Either name or path is required.');
            }
        }];
    }
}

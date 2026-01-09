<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_date' => ['sometimes', 'date'],
            'topics' => ['nullable', 'array'],
            'chapters' => ['nullable', 'array'],
            'objectives' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

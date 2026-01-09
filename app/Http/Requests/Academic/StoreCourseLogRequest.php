<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'uuid', 'exists:schedules,id'],
            'session_date' => ['required', 'date'],
            'topics' => ['nullable', 'array'],
            'chapters' => ['nullable', 'array'],
            'objectives' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

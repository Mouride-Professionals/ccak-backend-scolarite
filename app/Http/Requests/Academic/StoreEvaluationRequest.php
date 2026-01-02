<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'uuid', 'exists:courses,id'],
            'faculty_member_id' => ['required', 'uuid', 'exists:faculty_members,id'],
            'academic_year_id' => ['nullable', 'uuid', 'exists:academic_years,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'question_template' => ['required', 'array', 'min:1'],
            'response_deadline' => ['required', 'date'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}

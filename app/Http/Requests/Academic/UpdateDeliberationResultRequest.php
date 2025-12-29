<?php

namespace App\Http\Requests\Academic;

use App\Models\DeliberationResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliberationResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deliberation_session_id' => ['sometimes', 'uuid', 'exists:deliberation_sessions,id'],
            'student_id' => ['sometimes', 'uuid', 'exists:students,id'],
            'decision' => ['sometimes', Rule::in(DeliberationResult::DECISIONS)],
            'jury_remarks' => ['nullable', 'string'],
            'is_with_honors' => ['sometimes', 'boolean'],
            'honor_level' => ['nullable', Rule::in(DeliberationResult::HONOR_LEVELS)],
        ];
    }
}

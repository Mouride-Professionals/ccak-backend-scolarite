<?php

namespace App\Http\Requests\Academic;

use App\Models\DeliberationResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliberationResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deliberation_session_id' => ['required', 'uuid', 'exists:deliberation_sessions,id'],
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'decision' => ['required', Rule::in(DeliberationResult::DECISIONS)],
            'jury_remarks' => ['nullable', 'string'],
            'is_with_honors' => ['sometimes', 'boolean'],
            'honor_level' => ['nullable', Rule::in(DeliberationResult::HONOR_LEVELS), 'required_if:is_with_honors,true'],
        ];
    }
}

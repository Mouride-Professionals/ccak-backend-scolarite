<?php

namespace App\Http\Requests\Academic;

use App\Models\DeliberationSession;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliberationSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_program_id' => ['sometimes', 'uuid', 'exists:academic_programs,id'],
            'academic_year_id' => ['sometimes', 'uuid', 'exists:academic_years,id'],
            'semester' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'session_name' => ['sometimes', 'string', 'max:255'],
            'session_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:'.implode(',', DeliberationSession::getStatuses())],
            'presided_by' => ['sometimes', 'uuid', 'exists:users,id'],
            'jury_members' => ['nullable', 'array'],
            'jury_members.*' => ['uuid', 'exists:users,id'],
        ];
    }
}

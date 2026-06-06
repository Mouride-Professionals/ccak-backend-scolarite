<?php

namespace App\Http\Requests\Academic;

use App\Models\DeliberationSession;
use Illuminate\Foundation\Http\FormRequest;

class StoreDeliberationSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_program_id' => ['required', 'uuid', 'exists:academic_programs,id'],
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'semester' => ['required', 'integer', 'min:1', 'max:12'],
            'session_name' => ['required', 'string', 'max:255'],
            'session_date' => ['required', 'date'],
            'status' => ['nullable', 'in:'.implode(',', DeliberationSession::getStatuses())],
            'presided_by' => ['required', 'uuid', 'exists:users,id'],
            'jury_members' => ['nullable', 'array'],
            'jury_members.*' => ['uuid', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_program_id.required' => 'Le programme académique est requis.',
            'academic_year_id.required' => 'L\'année académique est requise.',
            'semester.required' => 'Le semestre est requis.',
            'session_name.required' => 'Le nom de la session est requis.',
            'session_date.required' => 'La date de session est requise.',
            'presided_by.required' => 'Le président est requis.',
        ];
    }
}

<?php

namespace App\Http\Requests\Academic;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamSessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreExamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'semester_number'  => ['required', 'integer', 'min:1', 'max:12'],
            'name'             => ['required', 'string', 'max:255'],
            'type'             => ['required', new Enum(ExamSessionType::class)],
            'start_date'       => ['required', 'date'],
            'end_date'         => ['required', 'date', 'after_or_equal:start_date'],
            'status'           => ['nullable', new Enum(ExamSessionStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => "L'année académique est requise.",
            'semester_number.required'  => 'Le numéro de semestre est requis.',
            'name.required'             => 'Le nom de la session est requis.',
            'type.required'             => 'Le type de session est requis.',
            'start_date.required'       => 'La date de début est requise.',
            'end_date.required'         => 'La date de fin est requise.',
            'end_date.after_or_equal'   => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}

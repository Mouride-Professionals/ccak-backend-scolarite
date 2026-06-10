<?php

namespace App\Http\Requests\Academic;

use App\Enums\AssessmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id'         => ['sometimes', 'uuid', 'exists:courses,id'],
            'faculty_member_id' => ['sometimes', 'uuid', 'exists:faculty_members,id'],
            'academic_year_id'  => ['sometimes', 'uuid', 'exists:academic_years,id'],
            'title'             => ['sometimes', 'string', 'max:255'],
            'type'              => ['sometimes', new Enum(AssessmentType::class)],
            'date'              => ['sometimes', 'date'],
            'start_time'        => ['nullable', 'date_format:H:i'],
            'duration_minutes'  => ['nullable', 'integer', 'min:1', 'max:480'],
            'room'              => ['nullable', 'string', 'max:100'],
            'coefficient'       => ['nullable', 'numeric', 'min:0', 'max:1'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.exists'          => 'La matière sélectionnée est introuvable.',
            'faculty_member_id.exists'  => "L'enseignant sélectionné est introuvable.",
            'academic_year_id.exists'   => "L'année académique est introuvable.",
            'date.date'                 => 'La date est invalide.',
            'start_time.date_format'    => "L'heure doit être au format HH:MM.",
            'coefficient.max'           => 'Le coefficient ne peut pas dépasser 1.',
        ];
    }
}

<?php

namespace App\Http\Requests\Academic;

use App\Enums\AssessmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id'          => ['required', 'uuid', 'exists:courses,id'],
            'faculty_member_id'  => ['required', 'uuid', 'exists:faculty_members,id'],
            'academic_year_id'   => ['required', 'uuid', 'exists:academic_years,id'],
            'title'              => ['required', 'string', 'max:255'],
            'type'               => ['required', new Enum(AssessmentType::class)],
            'date'               => ['required', 'date'],
            'start_time'         => ['nullable', 'date_format:H:i'],
            'duration_minutes'   => ['nullable', 'integer', 'min:1', 'max:480'],
            'room'               => ['nullable', 'string', 'max:100'],
            'coefficient'        => ['nullable', 'numeric', 'min:0', 'max:1'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required'         => 'La matière est requise.',
            'course_id.exists'           => 'La matière sélectionnée est introuvable.',
            'faculty_member_id.required' => "L'enseignant est requis.",
            'faculty_member_id.exists'   => "L'enseignant sélectionné est introuvable.",
            'academic_year_id.required'  => "L'année académique est requise.",
            'title.required'             => "L'intitulé du contrôle est requis.",
            'type.required'              => 'Le type de contrôle est requis.',
            'date.required'              => 'La date est requise.',
            'date.date'                  => 'La date est invalide.',
            'start_time.date_format'     => "L'heure doit être au format HH:MM.",
            'duration_minutes.integer'   => 'La durée doit être un entier.',
            'coefficient.numeric'        => 'Le coefficient doit être un nombre.',
            'coefficient.max'            => 'Le coefficient ne peut pas dépasser 1.',
        ];
    }
}

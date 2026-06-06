<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'uuid', 'exists:courses,id'],
            'room_id' => ['required', 'uuid', 'exists:rooms,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'invigilator_ids' => ['required', 'array', 'min:1'],
            'invigilator_ids.*' => ['uuid', 'exists:faculty_members,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'La matière est requise.',
            'room_id.required' => 'La salle est requise.',
            'date.required' => 'La date est requise.',
            'start_time.required' => "L'heure de début est requise.",
            'end_time.required' => "L'heure de fin est requise.",
            'end_time.after' => "L'heure de fin doit être après l'heure de début.",
            'invigilator_ids.required' => 'Au moins un surveillant est requis.',
            'invigilator_ids.min' => 'Au moins un surveillant est requis.',
        ];
    }
}

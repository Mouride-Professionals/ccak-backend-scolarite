<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['sometimes', 'uuid', 'exists:courses,id'],
            'room_id' => ['sometimes', 'uuid', 'exists:rooms,id'],
            'date' => ['sometimes', 'date'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'invigilator_ids' => ['sometimes', 'array', 'min:1'],
            'invigilator_ids.*' => ['uuid', 'exists:faculty_members,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

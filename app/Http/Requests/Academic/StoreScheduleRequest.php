<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
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
            'room_id' => ['required', 'uuid', 'exists:rooms,id'],
            'activity_type_id' => ['required', 'uuid', 'exists:activity_types,id'],
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'semester_number' => ['required', 'integer', 'min:1', 'max:12'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'recurrence_pattern' => ['nullable', 'array'],
        ];
    }
}

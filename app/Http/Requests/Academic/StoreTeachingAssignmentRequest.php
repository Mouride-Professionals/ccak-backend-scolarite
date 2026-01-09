<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeachingAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'faculty_member_id' => ['required', 'uuid', 'exists:faculty_members,id'],
            'course_id' => ['required', 'uuid', 'exists:courses,id'],
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'role' => ['required', 'string', 'in:TITULAR,TD,TP'],
            'hours_assigned' => ['required', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

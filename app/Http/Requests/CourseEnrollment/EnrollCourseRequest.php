<?php

namespace App\Http\Requests\CourseEnrollment;

use Illuminate\Foundation\Http\FormRequest;

class EnrollCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'uuid', 'exists:courses,id'],
            'semester' => ['required', 'integer', 'min:1', 'max:12'],
            'enrollment_date' => ['nullable', 'date'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\CourseEnrollment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('course_enrollment');

        return [
            'student_id' => ['sometimes', 'string', 'exists:students,id'],
            'course_id' => ['sometimes', 'string', 'exists:courses,id'],
            'enrollment_id' => ['sometimes', 'string', 'exists:enrollments,id', \Illuminate\Validation\Rule::unique('course_enrollments', 'enrollment_id')->ignore($id)],
            'course_id' => ['sometimes', 'string', 'exists:courses,id', \Illuminate\Validation\Rule::unique('course_enrollments', 'course_id')->ignore($id)],
            'academic_year_id' => ['sometimes', 'string', 'exists:academic_years,id', \Illuminate\Validation\Rule::unique('course_enrollments', 'academic_year_id')->ignore($id)],
            'semester' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'string', 'max:255'],
            'enrollment_date' => ['sometimes', 'date'],
            'drop_date' => ['nullable', 'date'],
        ];
    }
}

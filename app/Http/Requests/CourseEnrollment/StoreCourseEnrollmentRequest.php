<?php

declare(strict_types=1);

namespace App\Http\Requests\CourseEnrollment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|string|exists:students,id',
            'course_id' => 'required|string|exists:courses,id',
            'enrollment_id' => 'required|string|exists:enrollments,id|unique:course_enrollments,enrollment_id',
            'course_id' => 'required|string|exists:courses,id|unique:course_enrollments,course_id',
            'academic_year_id' => 'required|string|exists:academic_years,id|unique:course_enrollments,academic_year_id',
            'semester' => 'required|integer',
            'status' => 'required|string|max:255',
            'enrollment_date' => 'required|date',
            'drop_date' => 'nullable|date',
        ];
    }
}

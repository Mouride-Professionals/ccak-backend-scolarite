<?php

declare(strict_types=1);

namespace App\Http\Requests\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('enrollment');

        return [
            'student_id' => ['sometimes', 'string', 'exists:students,id', \Illuminate\Validation\Rule::unique('enrollments', 'student_id')->ignore($id)],
            'academic_program_id' => ['sometimes', 'string', 'exists:academic_programs,id', \Illuminate\Validation\Rule::unique('enrollments', 'academic_program_id')->ignore($id)],
            'academic_year_id' => ['sometimes', 'string', 'exists:academic_years,id', \Illuminate\Validation\Rule::unique('enrollments', 'academic_year_id')->ignore($id)],
            'current_semester' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'string', 'max:255'],
            'enrollment_date' => ['sometimes', 'date'],
            'registration_fee_paid' => ['sometimes', 'numeric'],
            'is_scholarship_holder' => ['sometimes', 'boolean'],
        ];
    }
}

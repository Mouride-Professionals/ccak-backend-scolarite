<?php

declare(strict_types=1);

namespace App\Http\Requests\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|string|exists:students,id',
            'academic_program_id' => 'required|string|exists:academic_programs,id',
            'academic_year_id' => 'required|string|exists:academic_years,id',
            'current_semester' => 'required|integer',
            'status' => 'required|string|max:255',
            'enrollment_date' => 'required|date',
            'registration_fee_paid' => 'required|numeric',
            'is_scholarship_holder' => 'required|boolean',
        ];
    }
}

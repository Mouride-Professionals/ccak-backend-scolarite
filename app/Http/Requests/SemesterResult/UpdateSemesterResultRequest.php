<?php

declare(strict_types=1);

namespace App\Http\Requests\SemesterResult;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSemesterResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('semester_result');

        return [
            'student_id' => ['sometimes', 'string', 'exists:students,id'],
            'academic_year_id' => ['sometimes', 'string', 'exists:academic_years,id'],
            'semester' => ['sometimes', 'integer'],
            'total_credits_enrolled' => ['sometimes', 'numeric'],
            'total_credits_earned' => ['sometimes', 'numeric'],
            'semester_average' => ['sometimes', 'numeric'],
            'semester_gpa' => ['sometimes', 'numeric'],
            'decision' => ['sometimes', 'string', 'max:255'],
            'calculated_by' => ['sometimes', 'string', 'exists:users,id'],
            'calculated_at' => ['sometimes', 'date'],
        ];
    }
}

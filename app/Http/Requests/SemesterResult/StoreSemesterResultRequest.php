<?php

declare(strict_types=1);

namespace App\Http\Requests\SemesterResult;

use Illuminate\Foundation\Http\FormRequest;

class StoreSemesterResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|string|exists:students,id',
            'academic_year_id' => 'required|string|exists:academic_years,id',
            'semester' => 'required|integer',
            'total_credits_enrolled' => 'required|numeric',
            'total_credits_earned' => 'required|numeric',
            'semester_average' => 'required|numeric',
            'semester_gpa' => 'required|numeric',
            'decision' => 'required|string|max:255',
            'calculated_by' => 'required|string|exists:users,id',
            'calculated_at' => 'required|date',
        ];
    }
}

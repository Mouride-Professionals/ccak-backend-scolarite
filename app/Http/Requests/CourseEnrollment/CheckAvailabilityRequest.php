<?php

namespace App\Http\Requests\CourseEnrollment;

use Illuminate\Foundation\Http\FormRequest;

class CheckAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'semester' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}

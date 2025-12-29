<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academicYearId = $this->route('academic_year');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('academic_years', 'name')->ignore($academicYearId)],
        ];
    }
}

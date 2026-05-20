<?php

namespace App\Http\Requests\Academic;

use App\Models\CourseUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_program_id' => ['required', 'uuid', 'exists:academic_programs,id'],
            'code' => ['required', 'string', 'max:50', 'unique:course_units,code'],
            'name' => ['required', 'string', 'max:255'],
            'semester_number' => ['required', 'integer', 'min:1'],
            'credits' => ['required', 'integer', 'min:0'],
            'coefficient' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'type' => ['required', Rule::in(CourseUnit::TYPES)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

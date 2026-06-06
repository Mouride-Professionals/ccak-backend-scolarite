<?php

namespace App\Http\Requests\Academic;

use App\Models\CourseUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseUnit = $this->route('course_unit');

        return [
            'academic_program_id' => ['sometimes', 'uuid', 'exists:academic_programs,id'],
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('course_units', 'code')->ignore($courseUnit),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'semester_number' => ['sometimes', 'integer', 'min:1'],
            'credits' => ['sometimes', 'integer', 'min:0'],
            'coefficient' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'type' => ['sometimes', Rule::in(CourseUnit::TYPES)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

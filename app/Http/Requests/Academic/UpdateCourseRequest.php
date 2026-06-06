<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $course = $this->route('course');

        return [
            'course_unit_id' => ['sometimes', 'uuid', 'exists:course_units,id'],
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('courses', 'code')->ignore($course),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'credits' => ['sometimes', 'integer', 'min:0'],
            'hours_lecture' => ['sometimes', 'integer', 'min:0'],
            'hours_td' => ['sometimes', 'integer', 'min:0'],
            'hours_tp' => ['sometimes', 'integer', 'min:0'],
            'hours_tpe' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'coefficient' => ['sometimes', 'numeric', 'min:0'],
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['uuid', 'exists:courses,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

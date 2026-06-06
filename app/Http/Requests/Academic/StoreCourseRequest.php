<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_unit_id' => ['required', 'uuid', 'exists:course_units,id'],
            'code' => ['required', 'string', 'max:50', 'unique:courses,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'credits' => ['required', 'integer', 'min:0'],
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

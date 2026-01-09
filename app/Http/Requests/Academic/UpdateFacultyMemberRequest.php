<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacultyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $facultyMember = $this->route('faculty_member');

        return [
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'staff_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('faculty_members', 'staff_number')->ignore($facultyMember),
            ],
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'rank' => ['nullable', 'string', 'in:PROFESSEUR,MAITRE_CONF,MAITRE_ASS,ASSISTANT,VACATAIRE'],
            'contract_type' => ['nullable', 'string', 'in:PERMANENT,TEMPORARY,HOURLY'],
            'hire_date' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

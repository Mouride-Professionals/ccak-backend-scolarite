<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacultyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'staff_number' => ['required', 'string', 'max:50', 'unique:faculty_members,staff_number'],
            'full_name' => ['required', 'string', 'max:255'],
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

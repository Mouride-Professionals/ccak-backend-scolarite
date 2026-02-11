<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:ACTIVE,SUSPENDED,GRADUATED,WITHDRAWN,EXPELLED'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut doit être l\'un des suivants : ACTIVE, SUSPENDED, GRADUATED, WITHDRAWN, EXPELLED.',
            'reason.max' => 'La raison ne peut pas dépasser 500 caractères.',
        ];
    }
}

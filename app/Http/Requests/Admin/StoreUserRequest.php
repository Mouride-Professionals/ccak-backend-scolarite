<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'role' => [
                'required',
                'string',
                Rule::in(['ADMIN', 'FACULTY', 'STAFF', 'STUDENT']),
            ],
            'full_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'employee_number' => [
                'nullable',
                'string',
                'max:50',
                'unique:faculty_members,employee_number', // If faculty
            ],
            'temporary_password' => [
                'nullable',
                'string',
                'min:8',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle doit être l\'un des suivants : ADMIN, FACULTY, STAFF, STUDENT.',
            'employee_number.unique' => 'Ce numéro d\'employé est déjà utilisé.',
            'temporary_password.min' => 'Le mot de passe temporaire doit contenir au moins 8 caractères.',
        ];
    }
}

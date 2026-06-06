<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'relationship' => ['sometimes', 'in:FATHER,MOTHER,GUARDIAN'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('guardians', 'email')->ignore($this->route('guardian')),
            ],
            'address' => ['sometimes', 'string'],
            'occupation' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.string' => 'Le nom complet doit être une chaîne de caractères.',
            'relationship.in' => 'La relation doit être FATHER, MOTHER ou GUARDIAN.',
            'phone.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'address.string' => 'L\'adresse doit être une chaîne de caractères.',
            'occupation.string' => 'La profession doit être une chaîne de caractères.',
        ];
    }
}

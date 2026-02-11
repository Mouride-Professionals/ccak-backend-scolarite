<?php

namespace App\Http\Requests\Student;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'in:FATHER,MOTHER,GUARDIAN'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'unique:guardians,email'],
            'address' => ['required', 'string'],
            'occupation' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Le nom complet est obligatoire.',
            'relationship.required' => 'La relation est obligatoire.',
            'relationship.in' => 'La relation doit être FATHER, MOTHER ou GUARDIAN.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'address.required' => 'L\'adresse est obligatoire.',
            'occupation.required' => 'La profession est obligatoire.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $student = $this->route('student');

                if (! $student instanceof Student) {
                    return;
                }

                if ($student->guardians()->count() >= 3) {
                    $validator->errors()->add('student_id', 'Un étudiant ne peut pas avoir plus de 3 tuteurs.');
                }
            },
        ];
    }
}

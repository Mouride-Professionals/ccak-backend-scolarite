<?php
declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'uuid',
                'exists:users,id',
                Rule::unique('students', 'user_id'),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:M,F'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'nationality' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'photo_url' => ['nullable', 'url'],
            'status' => ['sometimes', 'in:ACTIVE,SUSPENDED,GRADUATED,WITHDRAWN,EXPELLED'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.unique' => 'Un profil étudiant existe déjà pour cet utilisateur.',
            'user_id.exists' => 'L\'utilisateur spécifié n\'existe pas.',
            'gender.in' => 'Le genre doit être M ou F.',
            'date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'status.in' => 'Le statut doit être l\'un des suivants : ACTIVE, SUSPENDED, GRADUATED, WITHDRAWN, EXPELLED.',
        ];
    }
}

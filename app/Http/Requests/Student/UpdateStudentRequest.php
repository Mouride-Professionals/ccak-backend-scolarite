<?php
declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $studentId = $this->route('student');

        return [
            'student_number' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('students', 'student_number')->ignore($studentId),
            ],
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'gender' => ['sometimes', 'required', 'in:M,F'],
            'date_of_birth' => ['sometimes', 'required', 'date', 'before:today'],
            'place_of_birth' => ['sometimes', 'required', 'string', 'max:255'],
            'nationality' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'emergency_contact_name' => ['sometimes', 'required', 'string', 'max:255'],
            'emergency_contact_phone' => ['sometimes', 'required', 'string', 'max:20'],
            'address' => ['sometimes', 'required', 'string'],
            'photo_url' => ['sometimes', 'nullable', 'url'],
            'status' => ['sometimes', 'in:ACTIVE,SUSPENDED,GRADUATED,WITHDRAWN,EXPELLED'],
        ];
    }

    public function messages(): array
    {
        return [
            'gender.in' => 'Le genre doit être M ou F.',
            'date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'status.in' => 'Le statut doit être l\'un des suivants : ACTIVE, SUSPENDED, GRADUATED, WITHDRAWN, EXPELLED.',
        ];
    }
}

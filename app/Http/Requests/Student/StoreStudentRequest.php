<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Enums\DocumentType;
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
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
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
            'documents' => ['sometimes', 'array'],
            'documents.*.type' => ['required_with:documents', 'string', Rule::in(DocumentType::values())],
            'documents.*.notes' => ['nullable', 'string', 'max:1000'],
            'documents.*.document_file' => [
                'required_with:documents',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,jpg,jpeg,png,webp',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Un utilisateur existe déjà avec cet email.',
            'gender.in' => 'Le genre doit être M ou F.',
            'date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'status.in' => 'Le statut doit être l\'un des suivants : ACTIVE, SUSPENDED, GRADUATED, WITHDRAWN, EXPELLED.',
        ];
    }
}

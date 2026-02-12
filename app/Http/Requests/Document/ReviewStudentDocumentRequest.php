<?php

namespace App\Http\Requests\Document;

use App\Enums\DocumentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewStudentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    DocumentStatus::APPROVED->value,
                    DocumentStatus::REJECTED->value,
                ]),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est requis',
            'status.in' => 'Le statut n\'est pas valide',
            'notes.string' => 'Les notes doivent être du texte',
            'notes.max' => 'Les notes ne doivent pas dépasser 1000 caractères',
        ];
    }
}

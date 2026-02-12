<?php

namespace App\Http\Requests\Document;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(DocumentType::values())],
            'notes' => ['nullable', 'string', 'max:1000'],
            'document' => ['required', 'file'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de document est requis',
            'type.in' => 'Le type de document n\'est pas valide',
            'notes.string' => 'Les notes doivent être du texte',
            'notes.max' => 'Les notes ne doivent pas dépasser 1000 caractères',
            'document.required' => 'Un fichier est requis',
            'document.file' => 'Le fichier n\'est pas valide',
        ];
    }
}

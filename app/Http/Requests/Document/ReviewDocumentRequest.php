<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class ReviewDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required_if:action,reject', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.string' => 'Les notes doivent être du texte',
            'notes.max' => 'Les notes ne doivent pas dépasser 1000 caractères',
            'reason.required_if' => 'Une raison est requise pour le rejet',
            'reason.min' => 'La raison doit faire au moins 10 caractères',
            'reason.max' => 'La raison ne doit pas dépasser 500 caractères',
        ];
    }
}

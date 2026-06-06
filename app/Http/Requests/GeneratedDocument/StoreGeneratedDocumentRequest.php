<?php

declare(strict_types=1);

namespace App\Http\Requests\GeneratedDocument;

use App\Models\GeneratedDocument;
use Illuminate\Foundation\Http\FormRequest;

class StoreGeneratedDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|string',
            'document_number' => 'required|string|unique:generated_documents,document_number',
            'type' => 'required|string|in:'.implode(',', GeneratedDocument::getTypes()),
            'file_path' => 'required|string',
            'generated_by' => 'required|string',
            'metadata' => 'nullable|array',
            'generated_at' => 'nullable|date',
            'issued_at' => 'nullable|date',
            'status' => 'nullable|string|in:'.implode(',', GeneratedDocument::getStatuses()),
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'L\'identifiant de l\'étudiant est requis.',
            'student_id.uuid' => 'L\'identifiant de l\'étudiant doit être un UUID valide.',
            'student_id.exists' => 'L\'étudiant sélectionné n\'existe pas.',
            'type.required' => 'Le type de document est requis.',
            'type.in' => 'Le type de document sélectionné n\'est pas valide.',
            'metadata.array' => 'Les métadonnées doivent être un tableau.',
            'document_number.unique' => 'Ce numéro de document existe déjà.',
        ];
    }
}

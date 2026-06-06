<?php

declare(strict_types=1);

namespace App\Http\Requests\GeneratedDocument;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneratedDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('generated_document');

        return [
            'student_id' => ['sometimes', 'string'],
            'document_number' => ['sometimes', 'string', \Illuminate\Validation\Rule::unique('generated_documents', 'document_number')->ignore($id)],
            'file_path' => ['sometimes', 'string'],
            'generated_by' => ['sometimes', 'string'],
            'metadata' => ['nullable', 'array'],
            'generated_at' => ['nullable', 'date'],
            'issued_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(\App\Models\GeneratedDocument::getStatuses())],
        ];
    }
}

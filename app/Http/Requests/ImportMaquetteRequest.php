<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportMaquetteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'program_id' => ['required', 'uuid', 'exists:academic_programs,id'],
            'dry_run' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Un fichier Excel est requis.',
            'file.mimes' => 'Le fichier doit être au format Excel (.xlsx ou .xls).',
            'file.max' => 'Le fichier ne doit pas dépasser 20 Mo.',
            'program_id.required' => 'Le programme est requis.',
            'program_id.exists' => 'Le programme sélectionné est invalide.',
        ];
    }
}

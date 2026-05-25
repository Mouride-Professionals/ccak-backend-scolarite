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
            'file'          => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'program_name'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'program_level' => ['sometimes', 'in:LICENCE,MASTER,DOCTORAT'],
            'dry_run'       => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required'          => 'Un fichier Excel est requis.',
            'file.mimes'             => 'Le fichier doit être au format Excel (.xlsx ou .xls).',
            'file.max'               => 'Le fichier ne doit pas dépasser 20 Mo.',
            'department_id.required' => 'Le département est requis.',
            'department_id.exists'   => 'Le département sélectionné est invalide.',
        ];
    }
}

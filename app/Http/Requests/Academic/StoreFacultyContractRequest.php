<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacultyContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_type' => ['required', 'string', 'in:PERMANENT,TEMPORARY,HOURLY'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'terms' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:DRAFT,ACTIVE,EXPIRED,TERMINATED'],
            'is_current' => ['sometimes', 'boolean'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }
}

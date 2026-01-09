<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evaluation_id' => ['required', 'uuid', 'exists:evaluations,id'],
            'responses' => ['required', 'array', 'min:1'],
            'rating_scores' => ['nullable', 'array'],
            'comments' => ['nullable', 'string'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ];
    }
}

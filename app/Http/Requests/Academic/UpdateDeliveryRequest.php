<?php

namespace App\Http\Requests\Academic;

use App\Enums\TeachingDeliveryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', new Enum(TeachingDeliveryStatus::class)],
            'hours_cm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'hours_td' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'planned_start_date' => ['sometimes', 'nullable', 'date'],
            'effective_start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}

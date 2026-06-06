<?php

namespace App\Http\Requests\Academic;

use App\Enums\ExamSessionStatus;
use App\Enums\ExamSessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateExamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['sometimes', 'uuid', 'exists:academic_years,id'],
            'semester_number' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', new Enum(ExamSessionType::class)],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', new Enum(ExamSessionStatus::class)],
        ];
    }
}

<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'required',
                'uuid',
                'exists:academic_years,id',
                Rule::unique('academic_calendars', 'academic_year_id')->ignore($this->input('academic_year_id'), 'academic_year_id'),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'working_days' => ['required', 'array', 'min:1'],
            'weekend_days' => ['nullable', 'array'],
            'hour_slots' => ['required', 'array', 'min:1'],
            'break_slots' => ['nullable', 'array'],
        ];
    }
}

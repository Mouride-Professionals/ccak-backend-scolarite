<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class DashboardFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'uuid'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:12'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'department_id' => ['nullable', 'uuid'],
            'programme_id' => ['nullable', 'uuid'],
            'faculty_id' => ['nullable', 'uuid'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_filter(
            $this->validated(),
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );
    }
}

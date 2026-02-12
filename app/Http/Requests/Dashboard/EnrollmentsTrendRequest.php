<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard;

class EnrollmentsTrendRequest extends DashboardFiltersRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'period' => ['nullable', 'string', 'in:6m,12m,24m'],
        ]);
    }

    public function period(): string
    {
        return (string) ($this->input('period') ?: '6m');
    }
}

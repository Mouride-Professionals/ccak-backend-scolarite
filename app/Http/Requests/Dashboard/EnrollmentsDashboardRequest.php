<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard;

class EnrollmentsDashboardRequest extends DashboardFiltersRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'period' => ['nullable', 'string', 'in:6m,12m,24m'],
            'recent_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    public function period(): string
    {
        return (string) ($this->input('period') ?: '6m');
    }

    public function recentLimit(): int
    {
        return (int) ($this->integer('recent_limit') ?: 10);
    }
}

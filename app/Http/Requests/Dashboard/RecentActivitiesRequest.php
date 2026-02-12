<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard;

class RecentActivitiesRequest extends DashboardFiltersRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    public function limit(): int
    {
        return (int) ($this->integer('limit') ?: 10);
    }
}

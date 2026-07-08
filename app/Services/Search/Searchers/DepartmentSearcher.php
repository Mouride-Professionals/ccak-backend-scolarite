<?php

namespace App\Services\Search\Searchers;

use App\Models\Department;
use App\Services\Search\Contracts\SearcherInterface;
use Illuminate\Support\Collection;

class DepartmentSearcher implements SearcherInterface
{
    public function type(): string
    {
        return 'department';
    }

    public function requiredPermission(): string
    {
        return 'departments.view';
    }

    public function search(string $term, int $limit): Collection
    {
        // No withTrashed — soft-deleted departments are excluded
        return Department::search($term)
            ->limit($limit)
            ->get(['id', 'name', 'code'])
            ->map(fn (Department $d) => [
                'id' => $d->id,
                'type' => 'department',
                'label' => $d->name,
                'sublabel' => $d->code,
                'url_hint' => '/departments/'.$d->id,
            ]);
    }
}

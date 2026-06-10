<?php

namespace App\Services\Search\Searchers;

use App\Models\Faculty;
use App\Services\Search\Contracts\SearcherInterface;
use Illuminate\Support\Collection;

class FacultySearcher implements SearcherInterface
{
    public function type(): string
    {
        return 'faculty';
    }

    public function requiredPermission(): string
    {
        return 'faculties.view';
    }

    public function search(string $term, int $limit): Collection
    {
        return Faculty::search($term)
            ->limit($limit)
            ->get(['id', 'name', 'code'])
            ->map(fn (Faculty $f) => [
                'id'       => $f->id,
                'type'     => 'faculty',
                'label'    => $f->name,
                'sublabel' => $f->code,
                'url_hint' => '/faculties/' . $f->id,
            ]);
    }
}

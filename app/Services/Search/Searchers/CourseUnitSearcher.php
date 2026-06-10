<?php

namespace App\Services\Search\Searchers;

use App\Models\CourseUnit;
use App\Services\Search\Contracts\SearcherInterface;
use Illuminate\Support\Collection;

class CourseUnitSearcher implements SearcherInterface
{
    public function type(): string
    {
        return 'course_unit';
    }

    public function requiredPermission(): string
    {
        return 'course_units.view';
    }

    public function search(string $term, int $limit): Collection
    {
        return CourseUnit::search($term)
            ->limit($limit)
            ->get(['id', 'name', 'code'])
            ->map(fn (CourseUnit $u) => [
                'id'       => $u->id,
                'type'     => 'course_unit',
                'label'    => $u->name,
                'sublabel' => $u->code,
                'url_hint' => '/course-units/' . $u->id,
            ]);
    }
}

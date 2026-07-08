<?php

namespace App\Services\Search\Searchers;

use App\Models\Course;
use App\Services\Search\Contracts\SearcherInterface;
use Illuminate\Support\Collection;

class CourseSearcher implements SearcherInterface
{
    public function type(): string
    {
        return 'course';
    }

    public function requiredPermission(): string
    {
        return 'courses.view';
    }

    public function search(string $term, int $limit): Collection
    {
        return Course::search($term)
            ->limit($limit)
            ->get(['id', 'name', 'code'])
            ->map(fn (Course $c) => [
                'id' => $c->id,
                'type' => 'course',
                'label' => $c->name,
                'sublabel' => $c->code,
                'url_hint' => '/courses/'.$c->id.'/detail',
            ]);
    }
}

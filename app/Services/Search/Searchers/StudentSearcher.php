<?php

namespace App\Services\Search\Searchers;

use App\Models\Student;
use App\Services\Search\Contracts\SearcherInterface;
use Illuminate\Support\Collection;

class StudentSearcher implements SearcherInterface
{
    public function type(): string
    {
        return 'student';
    }

    public function requiredPermission(): string
    {
        return 'students.view';
    }

    public function search(string $term, int $limit): Collection
    {
        return Student::search($term)
            ->limit($limit)
            ->get(['id', 'full_name', 'student_number'])
            ->map(fn (Student $s) => [
                'id'        => $s->id,
                'type'      => 'student',
                'label'     => $s->full_name,
                'sublabel'  => $s->student_number,
                'url_hint'  => '/students/' . $s->id,
            ]);
    }
}

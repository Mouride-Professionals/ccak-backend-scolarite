<?php

namespace App\Services\Search\Searchers;

use App\Models\AcademicProgram;
use App\Services\Search\Contracts\SearcherInterface;
use Illuminate\Support\Collection;

class AcademicProgramSearcher implements SearcherInterface
{
    public function type(): string
    {
        return 'academic_program';
    }

    public function requiredPermission(): string
    {
        return 'academic_programs.view';
    }

    public function search(string $term, int $limit): Collection
    {
        return AcademicProgram::search($term)
            ->limit($limit)
            ->get(['id', 'name', 'level'])
            ->map(fn (AcademicProgram $p) => [
                'id'       => $p->id,
                'type'     => 'academic_program',
                'label'    => $p->name,
                'sublabel' => $p->level,
                'url_hint' => '/programmes/' . $p->id,
            ]);
    }
}

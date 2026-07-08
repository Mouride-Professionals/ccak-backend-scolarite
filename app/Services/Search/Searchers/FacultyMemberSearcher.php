<?php

namespace App\Services\Search\Searchers;

use App\Models\FacultyMember;
use App\Services\Search\Contracts\SearcherInterface;
use Illuminate\Support\Collection;

class FacultyMemberSearcher implements SearcherInterface
{
    public function type(): string
    {
        return 'faculty_member';
    }

    public function requiredPermission(): string
    {
        return 'faculty_members.view';
    }

    public function search(string $term, int $limit): Collection
    {
        return FacultyMember::with('department')
            ->search($term)
            ->limit($limit)
            ->get(['id', 'full_name', 'staff_number', 'department_id'])
            ->map(fn (FacultyMember $m) => [
                'id' => $m->id,
                'type' => 'faculty_member',
                'label' => $m->full_name,
                'sublabel' => $m->staff_number,
                'url_hint' => '/faculty-members/'.$m->id,
            ]);
    }
}

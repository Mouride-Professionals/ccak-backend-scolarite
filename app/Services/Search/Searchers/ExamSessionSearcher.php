<?php

namespace App\Services\Search\Searchers;

use App\Models\ExamSession;
use App\Services\Search\Contracts\SearcherInterface;
use Illuminate\Support\Collection;

class ExamSessionSearcher implements SearcherInterface
{
    public function type(): string
    {
        return 'exam_session';
    }

    public function requiredPermission(): string
    {
        return 'exam-sessions.view';
    }

    public function search(string $term, int $limit): Collection
    {
        return ExamSession::search($term)
            ->limit($limit)
            ->get(['id', 'name', 'type', 'status'])
            ->map(fn (ExamSession $e) => [
                'id' => $e->id,
                'type' => 'exam_session',
                'label' => $e->name,
                'sublabel' => $e->type instanceof \BackedEnum ? $e->type->value : (string) $e->type,
                'url_hint' => '/exams/'.$e->id,
            ]);
    }
}

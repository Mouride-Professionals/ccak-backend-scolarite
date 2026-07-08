<?php

namespace App\Services\Search;

use App\Models\User;
use App\Services\Search\Contracts\SearcherInterface;

class SearchService
{
    /** @param SearcherInterface[] $searchers */
    public function __construct(private readonly array $searchers) {}

    public function search(User $user, string $term, ?array $types, int $limit): array
    {
        $results = [];

        foreach ($this->searchers as $searcher) {
            if ($types !== null && ! in_array($searcher->type(), $types, true)) {
                continue;
            }

            if (! $user->can($searcher->requiredPermission())) {
                continue;
            }

            $hits = $searcher->search($term, $limit);
            array_push($results, ...$hits->all());
        }

        return [
            'query' => $term,
            'total' => count($results),
            'results' => $results,
        ];
    }
}

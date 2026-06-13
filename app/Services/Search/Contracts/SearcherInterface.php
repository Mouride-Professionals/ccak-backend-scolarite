<?php

namespace App\Services\Search\Contracts;

use Illuminate\Support\Collection;

interface SearcherInterface
{
    public function type(): string;

    public function requiredPermission(): string;

    public function search(string $term, int $limit): Collection;
}

<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\SearchRequest;
use App\Services\Search\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends BaseApiController
{
    public function __construct(private readonly SearchService $searchService) {}

    public function __invoke(SearchRequest $request): JsonResponse
    {
        $term  = $request->string('q')->toString();
        $types = $request->has('types') ? $request->input('types') : null;
        $limit = (int) $request->input('limit', 5);

        $data = $this->searchService->search(
            user: $request->user(),
            term: $term,
            types: $types,
            limit: $limit,
        );

        return $this->success($data, 'Recherche effectuée');
    }
}

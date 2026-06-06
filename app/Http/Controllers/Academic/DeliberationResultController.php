<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreDeliberationResultRequest;
use App\Http\Requests\Academic\UpdateDeliberationResultRequest;
use App\Http\Resources\Academic\DeliberationResultResource;
use App\Models\DeliberationResult;
use App\Services\DeliberationResultService;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DeliberationResultController extends BaseApiController
{
    public function __construct(
        protected DeliberationResultService $service)
    {
        // Add middleware here if needed, specially for permissions
        $this->middleware('permission:deliberation_results.view')->only(['index', 'show']);
        $this->middleware('permission:deliberation_results.create')->only('store');
        $this->middleware('permission:deliberation_results.update')->only('update');
        $this->middleware('permission:deliberation_results.delete')->only('destroy');
    }

    public function index()
    {
        $results = QueryBuilder::for(DeliberationResult::query())
            ->with(['student', 'deliberationSession'])
            ->allowedIncludes(['student', 'deliberationSession'])
            ->allowedFilters([
                AllowedFilter::exact('student_id'),
                AllowedFilter::exact('deliberation_session_id'),
                AllowedFilter::exact('decision'),
                AllowedFilter::exact('is_with_honors'),
            ])
            ->allowedSorts(['created_at'])
            ->defaultSort('-created_at')
            ->paginate(request()->integer('per_page') ?? 15)
            ->appends(request()->query());

        return $this->success(
            DeliberationResultResource::collection($results),
            'Deliberation results retrieved successfully'
        );
    }

    public function store(StoreDeliberationResultRequest $request)
    {
        $result = DB::transaction(fn () => $this->service->create($request->validated()));

        return $this->success(
            new DeliberationResultResource($result),
            'Deliberation result created',
            201
        );
    }

    public function show($id)
    {
        $result = $this->service->getById($id);

        return $result
            ? $this->success(new DeliberationResultResource($result))
            : $this->error('Not found', 404);
    }

    public function update(UpdateDeliberationResultRequest $request, $id)
    {
        $result = DB::transaction(fn () => $this->service->update($id, $request->validated()));

        return $result
            ? $this->success(new DeliberationResultResource($result))
            : $this->error('Not found', 404);
    }

    public function destroy($id)
    {
        return $this->service->delete($id)
            ? $this->success(null, 'Deleted')
            : $this->error('Not found', 404);
    }
}

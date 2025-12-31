<?php

namespace App\Http\Controllers\Academic;

use Illuminate\Http\Request;
use App\Services\DeliberationResultService;
use App\Http\Controllers\BaseApiController;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\DeliberationResult;

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

        return $this->success($results, 'Deliberation results retrieved successfully');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'deliberation_session_id' => 'required|uuid',
            'student_id' => 'required|uuid',
            'decision' => 'required|string',
            'is_with_honors' => 'sometimes|boolean',
        ]);

        $result = DB::transaction(fn() => $this->service->create($data));

        return $this->success($result, 'Deliberation result created', 201);
    }

    public function show($id)
    {
        $result = $this->service->getById($id);
        return $result ? $this->success($result) : $this->error('Not found', 404);
    }

    public function update(Request $request, $id)
    {
        $data = $request->only([
            'decision', 'is_with_honors'
        ]);

        $result = DB::transaction(fn() => $this->service->update($id, $data));

        return $result ? $this->success($result) : $this->error('Not found', 404);
    }

    public function destroy($id)
    {
        return $this->service->delete($id)
            ? $this->success(null, 'Deleted')
            : $this->error('Not found', 404);
    }
}

<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreActivityTypeRequest;
use App\Http\Resources\Academic\ActivityTypeResource;
use App\Models\ActivityType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityTypeController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:activity_types.view')->only('index');
        $this->middleware('permission:activity_types.create')->only('store');
    }

    public function index(Request $request): JsonResponse
    {
        $types = QueryBuilder::for(ActivityType::query())
            ->allowedFilters([
                AllowedFilter::scope('is_active'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['name', 'code', 'created_at'])
            ->defaultSort('name')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success(ActivityTypeResource::collection($types));
    }

    public function store(StoreActivityTypeRequest $request): JsonResponse
    {
        $type = DB::transaction(fn() => ActivityType::create($request->validated()));

        return $this->success(new ActivityTypeResource($type), 'Activity type created', 201);
    }
}

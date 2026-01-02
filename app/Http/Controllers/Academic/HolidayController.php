<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreHolidayRequest;
use App\Http\Requests\Academic\UpdateHolidayRequest;
use App\Http\Resources\Academic\HolidayResource;
use App\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class HolidayController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:holidays.view')->only(['index']);
        $this->middleware('permission:holidays.create')->only('store');
        $this->middleware('permission:holidays.update')->only('update');
        $this->middleware('permission:holidays.delete')->only('destroy');
    }

    public function index(Request $request): JsonResponse
    {
        $holidays = QueryBuilder::for(Holiday::query())
            ->allowedFilters([
                AllowedFilter::exact('academic_year_id'),
                AllowedFilter::exact('type'),
                AllowedFilter::scope('is_recurring'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['date', 'name', 'created_at'])
            ->defaultSort('date')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success(HolidayResource::collection($holidays));
    }

    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $holiday = DB::transaction(fn() => Holiday::create($request->validated()));

        return $this->success(new HolidayResource($holiday), 'Holiday created', 201);
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): JsonResponse
    {
        DB::transaction(fn() => $holiday->update($request->validated()));

        return $this->success(new HolidayResource($holiday->refresh()), 'Holiday updated');
    }

    public function destroy(Holiday $holiday): JsonResponse
    {
        DB::transaction(fn() => $holiday->delete());

        return $this->success(null, 'Holiday deleted');
    }
}

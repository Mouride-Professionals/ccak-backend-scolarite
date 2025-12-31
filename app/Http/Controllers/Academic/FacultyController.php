<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreFacultyRequest;
use App\Http\Requests\Academic\UpdateFacultyRequest;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FacultyController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:faculties.view')->only(['index', 'show']);
        $this->middleware('permission:faculties.create')->only('store');
        $this->middleware('permission:faculties.update')->only('update');
        $this->middleware('permission:faculties.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $faculties = QueryBuilder::for(Faculty::query())
            ->with(['dean', 'departments'])
            ->allowedIncludes(['dean', 'departments'])
            ->allowedFilters([
                AllowedFilter::callback('is_active', function ($query, $value) {
                    $isActive = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($isActive === null) {
                        return;
                    }

                    $query->where('is_active', $isActive);
                }),
                AllowedFilter::callback('search', function ($query, $value) {
                    $search = trim((string) $value);
                    if ($search === '') {
                        return;
                    }

                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
                }),
            ])
            ->allowedSorts(['name', 'code', 'created_at'])
            ->defaultSort('name')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success($faculties);
    }

    public function store(StoreFacultyRequest $request)
    {
        $faculty = DB::transaction(fn() => Faculty::create($request->validated()));

        return $this->success($faculty->load(['dean', 'departments']), 'Faculty created', Response::HTTP_CREATED);
    }

    public function show(Faculty $faculty)
    {
        return $this->success($faculty->load(['dean', 'departments']));
    }

    public function update(UpdateFacultyRequest $request, Faculty $faculty)
    {
        DB::transaction(fn() => $faculty->update($request->validated()));

        return $this->success($faculty->refresh()->load(['dean', 'departments']), 'Faculty updated');
    }

    public function destroy(Faculty $faculty)
    {
        DB::transaction(fn() => $faculty->delete());

        return $this->success(null, 'Faculty deleted');
    }
}

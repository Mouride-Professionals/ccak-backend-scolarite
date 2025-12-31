<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreCourseUnitRequest;
use App\Http\Requests\Academic\UpdateCourseUnitRequest;
use App\Models\CourseUnit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CourseUnitController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:course_units.view')->only(['index', 'show']);
        $this->middleware('permission:course_units.create')->only('store');
        $this->middleware('permission:course_units.update')->only('update');
        $this->middleware('permission:course_units.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $units = QueryBuilder::for(CourseUnit::query())
            ->with(['academicProgram', 'courses'])
            ->allowedIncludes(['academicProgram', 'courses'])
            ->allowedFilters([
                AllowedFilter::exact('academic_program_id'),
                AllowedFilter::exact('semester_number'),
                AllowedFilter::callback('is_active', function ($query, $value) {
                    $isActive = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($isActive === null) {
                        return;
                    }

                    $query->where('is_active', $isActive);
                }),
            ])
            ->allowedSorts(['semester_number', 'code', 'name', 'created_at'])
            ->defaultSort('semester_number')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success($units);
    }

    public function store(StoreCourseUnitRequest $request)
    {
        $unit = DB::transaction(fn() => CourseUnit::create($request->validated()));

        return $this->success($unit->load(['academicProgram', 'courses']), 'Course unit created', Response::HTTP_CREATED);
    }

    public function show(CourseUnit $courseUnit)
    {
        return $this->success($courseUnit->load(['academicProgram', 'courses']));
    }

    public function update(UpdateCourseUnitRequest $request, CourseUnit $courseUnit)
    {
        DB::transaction(fn() => $courseUnit->update($request->validated()));

        return $this->success($courseUnit->refresh()->load(['academicProgram', 'courses']), 'Course unit updated');
    }

    public function destroy(CourseUnit $courseUnit)
    {
        DB::transaction(fn() => $courseUnit->delete());

        return $this->success(null, 'Course unit deleted');
    }
}

<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreAcademicProgramRequest;
use App\Http\Requests\Academic\UpdateAcademicProgramRequest;
use App\Models\AcademicProgram;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AcademicProgramController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:academic_programs.view')->only(['index', 'show']);
        $this->middleware('permission:academic_programs.create')->only('store');
        $this->middleware('permission:academic_programs.update')->only('update');
        $this->middleware('permission:academic_programs.delete')->only('destroy');
    }


    public function index(Request $request)
    {
        $programs = QueryBuilder::for(AcademicProgram::query())
            ->with(['department', 'courseUnits'])
            ->allowedIncludes(['department', 'courseUnits'])
            ->allowedFilters([
                AllowedFilter::exact('department_id'),
                AllowedFilter::exact('level'),
                AllowedFilter::callback('is_active', function ($query, $value) {
                    $isActive = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($isActive === null) {
                        return;
                    }

                    $query->where('is_active', $isActive);
                }),
            ])
            ->allowedSorts(['name', 'level', 'created_at'])
            ->defaultSort('name')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success($programs);
    }

    public function store(StoreAcademicProgramRequest $request)
    {
        $program = DB::transaction(fn() => AcademicProgram::create($request->validated()));

        return $this->success($program->load(['department', 'courseUnits']), 'Academic program created', Response::HTTP_CREATED);
    }

    public function show(AcademicProgram $academicProgram)
    {
        return $this->success($academicProgram->load(['department', 'courseUnits']));
    }

    public function update(UpdateAcademicProgramRequest $request, AcademicProgram $academicProgram)
    {
        DB::transaction(fn() => $academicProgram->update($request->validated()));

        return $this->success($academicProgram->refresh()->load(['department', 'courseUnits']), 'Academic program updated');
    }

    public function destroy(AcademicProgram $academicProgram)
    {
        DB::transaction(fn() => $academicProgram->delete());

        return $this->success(null, 'Academic program deleted');
    }
}

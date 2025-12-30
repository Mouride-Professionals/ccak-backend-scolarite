<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreDepartmentRequest;
use App\Http\Requests\Academic\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DepartmentController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:departments.view')->only(['index', 'show']);
        $this->middleware('permission:departments.create')->only('store');
        $this->middleware('permission:departments.update')->only('update');
        $this->middleware('permission:departments.delete')->only('destroy');
    }

    public function index()
    {
        $departments = QueryBuilder::for(Department::query())
            ->with(['faculty', 'head', 'programs'])
            ->allowedIncludes(['faculty', 'head', 'programs'])
            ->allowedFilters([
                AllowedFilter::exact('faculty_id'),
                AllowedFilter::callback('is_active', function ($query, $value) {
                    $isActive = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($isActive === null) {
                        return;
                    }

                    $query->where('is_active', $isActive);
                }),
            ])
            ->allowedSorts(['name', 'code', 'created_at'])
            ->defaultSort('name')
            ->get();

        return $this->success($departments);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create($request->validated());

        return $this->success($department->load(['faculty', 'head', 'programs']), 'Department created', Response::HTTP_CREATED);
    }

    public function show(Department $department)
    {
        return $this->success($department->load(['faculty', 'head', 'programs']));
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());

        return $this->success($department->refresh()->load(['faculty', 'head', 'programs']), 'Department updated');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return $this->success(null, 'Department deleted');
    }
}

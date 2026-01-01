<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreFacultyMemberRequest;
use App\Http\Requests\Academic\UpdateFacultyMemberRequest;
use App\Http\Resources\Academic\FacultyMemberResource;
use App\Models\FacultyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FacultyMemberController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:faculty_members.view')->only(['index', 'show']);
        $this->middleware('permission:faculty_members.create')->only('store');
        $this->middleware('permission:faculty_members.update')->only('update');
        $this->middleware('permission:faculty_members.delete')->only('destroy');
    }

    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $members = QueryBuilder::for(FacultyMember::query())
            ->with(['department', 'user'])
            ->allowedIncludes(['department', 'user'])
            ->allowedFilters([
                AllowedFilter::exact('department_id'),
                AllowedFilter::exact('rank'),
                AllowedFilter::exact('contract_type'),
                AllowedFilter::scope('is_active'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['full_name', 'staff_number', 'created_at'])
            ->defaultSort('full_name')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success(FacultyMemberResource::collection($members), 'Faculty members retrieved successfully');
    }

    public function store(StoreFacultyMemberRequest $request): JsonResponse
    {
        $member = DB::transaction(fn() => FacultyMember::create($request->validated()));

        return $this->success(
            new FacultyMemberResource($member->load(['department', 'user'])),
            'Faculty member created',
            201
        );
    }

    public function show(FacultyMember $facultyMember): JsonResponse
    {
        return $this->success(new FacultyMemberResource($facultyMember->load(['department', 'user'])));
    }

    public function update(UpdateFacultyMemberRequest $request, FacultyMember $facultyMember): JsonResponse
    {
        DB::transaction(fn() => $facultyMember->update($request->validated()));

        return $this->success(
            new FacultyMemberResource($facultyMember->refresh()->load(['department', 'user'])),
            'Faculty member updated'
        );
    }

    public function destroy(FacultyMember $facultyMember): JsonResponse
    {
        DB::transaction(fn() => $facultyMember->delete());

        return $this->success(null, 'Faculty member deleted');
    }
}

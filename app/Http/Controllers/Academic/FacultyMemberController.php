<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Models\FacultyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function index(Request $request): JsonResponse
    {
        $members = QueryBuilder::for(FacultyMember::query())
            ->with(['department', 'user'])
            ->allowedIncludes(['department', 'user'])
            ->allowedFilters([
                AllowedFilter::exact('department_id'),
                AllowedFilter::exact('rank'),
                AllowedFilter::exact('contract_type'),
                AllowedFilter::partial('full_name'),
                AllowedFilter::partial('staff_number'),
            ])
            ->allowedSorts(['full_name', 'staff_number', 'created_at'])
            ->defaultSort('full_name')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success($members, 'Faculty members retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'staff_number' => 'required|string|max:50|unique:faculty_members,staff_number',
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'rank' => 'nullable|string|in:PROFESSEUR,MAITRE_CONF,MAITRE_ASS,ASSISTANT,VACATAIRE',
            'contract_type' => 'nullable|string|in:PERMANENT,TEMPORARY,HOURLY',
            'hire_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $member = DB::transaction(fn() => FacultyMember::create($data));

        return $this->success($member->load(['department', 'user']), 'Faculty member created', 201);
    }

    public function show(FacultyMember $facultyMember): JsonResponse
    {
        return $this->success($facultyMember->load(['department', 'user']));
    }

    public function update(Request $request, FacultyMember $facultyMember): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'staff_number' => 'sometimes|required|string|max:50|unique:faculty_members,staff_number,' . $facultyMember->id,
            'full_name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'rank' => 'nullable|string|in:PROFESSEUR,MAITRE_CONF,MAITRE_ASS,ASSISTANT,VACATAIRE',
            'contract_type' => 'nullable|string|in:PERMANENT,TEMPORARY,HOURLY',
            'hire_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        DB::transaction(fn() => $facultyMember->update($data));

        return $this->success($facultyMember->refresh()->load(['department', 'user']), 'Faculty member updated');
    }

    public function destroy(FacultyMember $facultyMember): JsonResponse
    {
        DB::transaction(fn() => $facultyMember->delete());

        return $this->success(null, 'Faculty member deleted');
    }
}

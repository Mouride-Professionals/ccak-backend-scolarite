<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreFacultyMemberRequest;
use App\Http\Requests\Academic\UpdateFacultyMemberRequest;
use App\Http\Resources\Academic\FacultyMemberResource;
use App\Http\Resources\Academic\TeachingAssignmentResource;
use App\Models\FacultyMember;
use App\Models\CourseEnrollment;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Str;

class FacultyMemberController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:faculty_members.view')->only(['index', 'show', 'assignments', 'workload', 'available']);
        $this->middleware('permission:faculty_members.create')->only('store');
        $this->middleware('permission:faculty_members.update')->only('update');
        $this->middleware('permission:faculty_members.delete')->only('destroy');
    }

    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $members = QueryBuilder::for(FacultyMember::query())
            ->with(['department', 'user'])
            ->allowedIncludes(['department', 'user', 'teachingAssignments', 'documents', 'contracts'])
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
        $data = $request->validated();

        $member = DB::transaction(function () use ($data, $request) {
            $userId = $data['user_id'] ?? null;
            if (!$userId) {
                $user = User::create([
                    'email' => $data['email'],
                    'password' => Str::random(32),
                    'keycloak_id' => $data['keycloak_id'] ?? null,
                    'is_active' => true,
                ]);
                $user->assignRole('FACULTY');
                app(NotificationService::class)->sendWelcome($user);
                $userId = $user->id;
            } else {
                $user = User::findOrFail($userId);
                if (!$user->hasRole('FACULTY')) {
                    $user->assignRole('FACULTY');
                }
            }

            if (empty($data['staff_number'])) {
                $data['staff_number'] = $this->generateStaffNumber();
            }

            $data['user_id'] = $userId;

            return FacultyMember::create($data);
        });

        return $this->success(
            new FacultyMemberResource($member->load(['department', 'user'])),
            'Faculty member created',
            201
        );
    }

    public function show(FacultyMember $faculty_member): JsonResponse
    {
        $this->authorize('view', $faculty_member);

        return $this->success(
            new FacultyMemberResource(
                $faculty_member->load(['department', 'user', 'teachingAssignments.course.courseUnit', 'teachingAssignments.academicYear', 'documents', 'contracts'])
            )
        );
    }

    public function update(UpdateFacultyMemberRequest $request, FacultyMember $faculty_member): JsonResponse
    {
        $this->authorize('update', $faculty_member);

        DB::transaction(fn() => $faculty_member->update($request->validated()));

        return $this->success(
            new FacultyMemberResource(
                $faculty_member->refresh()->load(['department', 'user', 'teachingAssignments.course.courseUnit', 'teachingAssignments.academicYear', 'documents', 'contracts'])
            ),
            'Faculty member updated'
        );
    }

    public function destroy(FacultyMember $faculty_member): JsonResponse
    {
        DB::transaction(fn() => $faculty_member->delete());

        return $this->success(null, 'Faculty member deleted');
    }

    public function assignments(\Illuminate\Http\Request $request, FacultyMember $faculty_member): JsonResponse
    {
        $semester = $request->integer('semester');
        if ($semester === 0 && $request->boolean('current_semester')) {
            $semester = 1;
        }

        $assignments = TeachingAssignment::query()
            ->where('faculty_member_id', $faculty_member->id)
            ->with(['course.courseUnit', 'academicYear'])
            ->when($request->filled('academic_year_id'), function ($query) use ($request) {
                $query->where('academic_year_id', $request->input('academic_year_id'));
            })
            ->when($semester > 0, function ($query) use ($semester) {
                $query->whereHas('course.courseUnit', function ($sub) use ($semester) {
                    $sub->where('semester_number', $semester);
                });
            })
            ->get();

        $totalHours = $assignments->sum('hours_assigned');

        return $this->success([
            'total_hours' => $totalHours,
            'assignments' => TeachingAssignmentResource::collection($assignments),
        ]);
    }

    public function workload(\Illuminate\Http\Request $request, FacultyMember $faculty_member): JsonResponse
    {
        $academicYearId = $request->input('academic_year_id');

        $assignments = TeachingAssignment::query()
            ->where('faculty_member_id', $faculty_member->id)
            ->with('course.courseUnit')
            ->when($academicYearId, function ($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })
            ->get();

        $bySemester = $assignments->groupBy(function ($assignment) {
            return $assignment->course?->courseUnit?->semester_number ?? 0;
        })->map(function ($items) use ($academicYearId) {
            $total = $items->sum('hours_assigned');
            $byType = $items->groupBy('role')->map(fn($group) => $group->sum('hours_assigned'));
            $courseIds = $items->pluck('course_id')->unique();
            $courseCount = $courseIds->count();
            $studentCount = $courseIds->isEmpty()
                ? 0
                : CourseEnrollment::whereIn('course_id', $courseIds)
                    ->when($academicYearId, function ($query) use ($academicYearId) {
                        $query->where('academic_year_id', $academicYearId);
                    })
                    ->distinct('student_id')
                    ->count('student_id');

            return [
                'total_hours' => $total,
                'hours_by_type' => $byType,
                'course_count' => $courseCount,
                'student_count' => $studentCount,
                'overload' => $total > 40,
            ];
        });

        return $this->success([
            'faculty_member_id' => $faculty_member->id,
            'semesters' => $bySemester,
        ]);
    }

    public function available(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = FacultyMember::query()->where('is_active', true);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('rank')) {
            $query->where('rank', $request->input('rank'));
        }

        if ($request->filled('course_id')) {
            $query->whereDoesntHave('teachingAssignments', function ($sub) use ($request) {
                $sub->where('course_id', $request->input('course_id'));
                if ($request->filled('academic_year_id')) {
                    $sub->where('academic_year_id', $request->input('academic_year_id'));
                }
            });
        }

        $members = $query->with(['department'])->get();

        return $this->success(FacultyMemberResource::collection($members));
    }

    private function generateStaffNumber(): string
    {
        $prefix = 'FM-';
        $last = FacultyMember::where('staff_number', 'like', $prefix . '%')
            ->orderBy('staff_number', 'desc')
            ->lockForUpdate()
            ->first();

        $next = 1;
        if ($last && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $last->staff_number, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

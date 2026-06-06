<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreCourseRequest;
use App\Http\Requests\Academic\UpdateCourseRequest;
use App\Http\Resources\Academic\CourseResource;
use App\Models\Course;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CourseController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:courses.view')->only(['index', 'show']);
        $this->middleware('permission:courses.create')->only('store');
        $this->middleware('permission:courses.update')->only('update');
        $this->middleware('permission:courses.delete')->only('destroy');
        $this->middleware('permission:grades.view')->only('grades');
    }

    public function index(Request $request)
    {
        $courses = QueryBuilder::for(Course::query())
            ->with(['courseUnit.academicProgram'])
            ->allowedIncludes(['courseUnit', 'courseUnit.academicProgram'])
            ->allowedFilters([
                AllowedFilter::exact('course_unit_id'),
                AllowedFilter::scope('is_active'),
                AllowedFilter::scope('program_level'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['name', 'code', 'credits', 'created_at'])
            ->defaultSort('name')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success(CourseResource::collection($courses));
    }

    public function store(StoreCourseRequest $request)
    {
        $course = DB::transaction(fn () => Course::create($request->validated()));

        return $this->success(
            new CourseResource($course->load(['courseUnit.academicProgram'])),
            'Course created',
            Response::HTTP_CREATED
        );
    }

    public function show(Course $course)
    {
        return $this->success(new CourseResource($course->load(['courseUnit.academicProgram'])));
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        DB::transaction(fn () => $course->update($request->validated()));

        return $this->success(
            new CourseResource($course->refresh()->load(['courseUnit.academicProgram'])),
            'Course updated'
        );
    }

    public function destroy(Course $course)
    {
        DB::transaction(fn () => $course->delete());

        return $this->success(null, 'Course deleted');
    }

    /**
     * Get all grades for a specific course
     * GET /api/courses/{id}/grades
     * Faculty only endpoint with filtering and sorting
     */
    public function grades(Request $request, Course $course)
    {
        // Validate faculty authorization
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['FACULTY', 'ADMIN'])) {
            return $this->error('Only faculty members and administrators are authorized to view course grades.', Response::HTTP_FORBIDDEN);
        }

        // Validate request parameters
        $validated = $request->validate([
            'type' => 'nullable|string|in:CC,EXAM,TP,ORAL',
            'sort_by' => 'nullable|string|in:student_name,score,type,status,created_at',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'status' => 'nullable|string|in:DRAFT,SUBMITTED,VALIDATED,PUBLISHED',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = QueryBuilder::for(Grade::query())
            ->with(['student', 'courseEnrollment', 'enteredBy'])
            ->where('course_id', $course->id)
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('status'),
            ]);

        // Apply sorting
        $sortBy = $validated['sort_by'] ?? 'student_name';
        $sortDirection = $validated['sort_direction'] ?? 'asc';
        $perPage = $validated['per_page'] ?? 25;

        if ($sortBy === 'student_name') {
            $query->join('students', 'grades.student_id', '=', 'students.id')
                ->orderBy('students.full_name', $sortDirection)
                ->select('grades.*');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $grades = $query->paginate($perPage)->appends($request->query());

        // Transform the data for response
        $gradeData = collect($grades->items())->map(function ($grade) {
            return [
                'id' => $grade->id,
                'student' => [
                    'id' => $grade->student->id,
                    'student_number' => $grade->student->student_number,
                    'full_name' => $grade->student->full_name,
                ],
                'type' => $grade->type,
                'score' => $grade->score,
                'max_score' => $grade->max_score,
                'weight' => $grade->weight,
                'percentage' => $grade->max_score > 0 ? round(($grade->score / $grade->max_score) * 100, 2) : 0,
                'status' => $grade->status,
                'entered_by' => $grade->enteredBy ? [
                    'id' => $grade->enteredBy->id,
                    'email' => $grade->enteredBy->email,
                ] : null,
                'entered_at' => $grade->entered_at,
                'validated_at' => $grade->validated_at,
                'created_at' => $grade->created_at,
                'updated_at' => $grade->updated_at,
            ];
        });

        // Get course statistics
        $statistics = $this->calculateCourseStatistics($course);

        return $this->success([
            'course' => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'credits' => $course->credits,
                'coefficient' => $course->coefficient,
            ],
            'filters' => [
                'type' => $validated['type'] ?? null,
                'status' => $validated['status'] ?? null,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
            'statistics' => $statistics,
            'grades' => $gradeData,
            'pagination' => [
                'current_page' => $grades->currentPage(),
                'last_page' => $grades->lastPage(),
                'per_page' => $grades->perPage(),
                'total' => $grades->total(),
            ],
        ], 'Course grades retrieved successfully');
    }

    /**
     * Calculate statistics for grades in a course
     */
    private function calculateCourseStatistics(Course $course): array
    {
        $allGrades = Grade::where('course_id', $course->id)->get();
        $enrolledStudents = $course->students()->count();

        if ($allGrades->isEmpty()) {
            return [
                'total_grades' => 0,
                'enrolled_students' => $enrolledStudents,
                'students_with_grades' => 0,
                'by_type' => [],
                'by_status' => [],
                'averages' => [
                    'overall_average' => 0,
                    'overall_percentage' => 0,
                ],
            ];
        }

        // Group by type
        $byType = $allGrades->groupBy('type')->map(function ($grades) {
            return [
                'count' => $grades->count(),
                'average_score' => round($grades->avg('score'), 2),
                'average_percentage' => round($grades->map(function ($grade) {
                    return $grade->max_score > 0 ? ($grade->score / $grade->max_score) * 100 : 0;
                })->avg(), 2),
            ];
        });

        // Group by status
        $byStatus = $allGrades->groupBy('status')->map(function ($grades) {
            return $grades->count();
        });

        return [
            'total_grades' => $allGrades->count(),
            'enrolled_students' => $enrolledStudents,
            'students_with_grades' => $allGrades->pluck('student_id')->unique()->count(),
            'by_type' => $byType,
            'by_status' => $byStatus,
            'averages' => [
                'overall_average' => round($allGrades->avg('score'), 2),
                'overall_percentage' => round($allGrades->map(function ($grade) {
                    return $grade->max_score > 0 ? ($grade->score / $grade->max_score) * 100 : 0;
                })->avg(), 2),
            ],
        ];
    }
}

<?php

namespace App\Http\Controllers\Academic;

use App\Enums\TeachingDeliveryStatus;
use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\ImportTeachingAssignmentRequest;
use App\Http\Requests\Academic\StoreTeachingAssignmentRequest;
use App\Http\Requests\Academic\UpdateDeliveryRequest;
use App\Http\Resources\Academic\TeachingAssignmentResource;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\FacultyMember;
use App\Models\Grade;
use App\Models\TeachingAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TeachingAssignmentController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:teaching_assignments.create')->only(['store', 'import']);
        $this->middleware('permission:teaching_assignments.delete')->only('destroy');
        $this->middleware('permission:teaching_assignments.view')->only(['index', 'planning', 'planningDashboard']);
        $this->middleware('permission:teaching_assignments.update')->only('updateDelivery');
    }

    public function index(Request $request): JsonResponse
    {
        $assignments = QueryBuilder::for(TeachingAssignment::query())
            ->with(['facultyMember', 'course', 'academicYear'])
            ->allowedIncludes(['facultyMember', 'course', 'academicYear'])
            ->allowedFilters([
                AllowedFilter::exact('faculty_member_id'),
                AllowedFilter::exact('course_id'),
                AllowedFilter::exact('academic_year_id'),
                AllowedFilter::exact('role'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['hours_assigned', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success(TeachingAssignmentResource::collection($assignments), 'Teaching assignments retrieved successfully');
    }

    public function store(StoreTeachingAssignmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $exists = TeachingAssignment::where('faculty_member_id', $data['faculty_member_id'])
            ->where('course_id', $data['course_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->exists();

        if ($exists) {
            return $this->error('Assignment already exists for this faculty, course, and academic year.', 422);
        }

        $assignment = DB::transaction(fn() => TeachingAssignment::create($data));

        return $this->success(
            new TeachingAssignmentResource($assignment->load(['course', 'academicYear'])),
            'Teaching assignment created',
            201
        );
    }

    public function destroy(TeachingAssignment $teachingAssignment): JsonResponse
    {
        $hasGrades = Grade::where('course_id', $teachingAssignment->course_id)->exists();

        if ($hasGrades) {
            return $this->error('Cannot remove assignment: grades already exist for this course.', 422);
        }

        DB::transaction(fn() => $teachingAssignment->delete());

        return $this->success(null, 'Teaching assignment removed');
    }

    public function planning(Request $request): JsonResponse
    {
        $request->validate([
            'program_id'       => ['sometimes', 'uuid', 'exists:academic_programs,id'],
            'academic_year_id' => ['sometimes', 'uuid', 'exists:academic_years,id'],
        ]);

        $assignments = QueryBuilder::for(TeachingAssignment::query())
            ->with(['facultyMember', 'course.courseUnit.academicProgram', 'academicYear'])
            ->allowedFilters([
                AllowedFilter::exact('academic_year_id'),
                AllowedFilter::exact('role'),
                AllowedFilter::exact('status'),
                AllowedFilter::scope('search'),
                AllowedFilter::callback('program_id', function ($query, $value) {
                    $query->whereHas('course.courseUnit', fn($q) => $q->where('academic_program_id', $value));
                }),
            ])
            ->allowedSorts(['created_at', 'status', 'planned_start_date'])
            ->defaultSort('status')
            ->get();

        return $this->success(TeachingAssignmentResource::collection($assignments), 'Planning retrieved successfully');
    }

    public function planningDashboard(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => ['sometimes', 'uuid', 'exists:academic_years,id'],
            'program_id'       => ['sometimes', 'uuid', 'exists:academic_programs,id'],
        ]);

        $query = TeachingAssignment::query()
            ->with('course.courseUnit.academicProgram')
            ->where('academic_year_id', $request->academic_year_id);

        if ($request->program_id) {
            $query->whereHas('course.courseUnit', fn($q) => $q->where('academic_program_id', $request->program_id));
        }

        $assignments = $query->get();

        $byProgram = $assignments->groupBy(fn($a) => $a->course?->courseUnit?->academicProgram?->id);

        $dashboard = $byProgram->map(function ($items, $programId) {
            $program = $items->first()?->course?->courseUnit?->academicProgram;
            $total   = $items->count();

            $completed  = $items->filter(fn($a) => $a->status === TeachingDeliveryStatus::COMPLETED)->count();
            $started    = $items->filter(fn($a) => $a->status !== TeachingDeliveryStatus::NOT_STARTED)->count();

            return [
                'program_id'       => $programId,
                'program_name'     => $program?->name,
                'total'            => $total,
                'completed'        => $completed,
                'started'          => $started,
                'taux_execution'   => $total > 0 ? round($started / $total * 100, 1) : 0,
                'taux_achevement'  => $total > 0 ? round($completed / $total * 100, 1) : 0,
            ];
        })->values();

        return $this->success($dashboard, 'Planning dashboard retrieved successfully');
    }

    public function updateDelivery(UpdateDeliveryRequest $request, TeachingAssignment $teachingAssignment): JsonResponse
    {
        $teachingAssignment->update($request->validated());

        return $this->success(
            new TeachingAssignmentResource($teachingAssignment->load(['course', 'facultyMember', 'academicYear'])),
            'Delivery updated'
        );
    }

    public function import(ImportTeachingAssignmentRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $rows = array_map('str_getcsv', preg_split('/\r\n|\n|\r/', trim((string) $file->get())));
        if (empty($rows)) {
            return $this->error('Empty import file.', 422);
        }

        $header = array_map(fn($value) => strtolower(trim((string) $value)), array_shift($rows));
        $required = ['faculty_member_id', 'course_id', 'academic_year_id', 'role', 'hours_assigned', 'hourly_rate'];
        if ($header !== $required) {
            return $this->error('Invalid header. Expected: ' . implode(',', $required), 422);
        }

        $errors = [];
        $created = 0;

        DB::transaction(function () use ($rows, &$errors, &$created): void {
            foreach ($rows as $index => $row) {
                if (count($row) < 5) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Missing required columns'];
                    continue;
                }

                [$facultyId, $courseId, $yearId, $role, $hours, $rate] = array_pad($row, 6, null);
                $faculty = FacultyMember::find($facultyId);
                $course = Course::find($courseId);
                $year = AcademicYear::find($yearId);

                if (!$faculty || !$course || !$year) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Invalid faculty, course, or academic year'];
                    continue;
                }

                if (!in_array($role, ['TITULAR', 'TD', 'TP'], true)) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Invalid role'];
                    continue;
                }

                if (!is_numeric($hours) || (float) $hours < 0) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Invalid hours_assigned'];
                    continue;
                }

                $exists = TeachingAssignment::where('faculty_member_id', $facultyId)
                    ->where('course_id', $courseId)
                    ->where('academic_year_id', $yearId)
                    ->exists();

                if ($exists) {
                    $errors[] = ['row' => $index + 2, 'error' => 'Assignment already exists'];
                    continue;
                }

                TeachingAssignment::create([
                    'faculty_member_id' => $facultyId,
                    'course_id' => $courseId,
                    'academic_year_id' => $yearId,
                    'role' => $role,
                    'hours_assigned' => $hours,
                    'hourly_rate' => is_numeric($rate) ? $rate : null,
                ]);

                $created++;
            }
        });

        return $this->success([
            'created' => $created,
            'errors' => $errors,
        ], 'Import completed');
    }
}

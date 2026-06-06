<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Grade\StoreGradeRequest;
use App\Http\Requests\Grade\UpdateGradeRequest;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Repositories\GradeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class GradeController extends BaseApiController
{
    public function __construct(private readonly GradeRepository $repository)
    {
        $this->middleware('permission:grades.view')->only(['index', 'show']);
        $this->middleware('permission:grades.create')->only('store');
        $this->middleware('permission:grades.update')->only('update');
        $this->middleware('permission:grades.delete')->only('destroy');
        $this->middleware('permission:grades.submit')->only('submit');
        $this->middleware('permission:grades.validate')->only('validateGrade');
        $this->middleware('permission:grades.publish')->only('publish');
    }

    public function index(Request $request): JsonResponse
    {
        $grades = QueryBuilder::for(Grade::query())
            ->with(['student', 'course', 'courseEnrollment', 'enteredBy'])
            ->allowedIncludes(['student', 'course', 'courseEnrollment', 'enteredBy'])
            ->allowedFilters([
                AllowedFilter::exact('student_id'),
                AllowedFilter::exact('course_id'),
                AllowedFilter::exact('course_enrollment_id'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('status'),
                AllowedFilter::scope('entered_between'),
                AllowedFilter::scope('validated_between'),
            ])
            ->allowedSorts(['entered_at', 'validated_at', 'score', 'created_at'])
            ->defaultSort('-entered_at')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success($grades, 'Grades retrieved successfully');
    }

    public function store(StoreGradeRequest $request): JsonResponse
    {
        $item = DB::transaction(function () use ($request) {
            return $this->repository->create([
                ...$request->validated(),
                'entered_by' => auth()->id(),
                'status' => 'DRAFT',
            ]);
        });

        return $this->success(new GradeResource($item), 'Grade created successfully', 201);
    }

    public function show(int|string $grade): JsonResponse
    {
        return $this->success(new GradeResource($this->repository->find($grade)), 'Grade retrieved successfully');
    }

    public function update(UpdateGradeRequest $request, int|string $grade): JsonResponse
    {
        $gradeModel = $this->repository->find($grade);

        // Store old values for audit log
        $oldValues = $gradeModel->only([
            'course_enrollment_id', 'student_id', 'course_id',
            'type', 'score', 'max_score', 'weight',
        ]);

        // Update the grade
        $item = DB::transaction(fn () => $this->repository->update($grade, $request->validated()));

        // Log the audit trail
        $changes = [];
        foreach ($request->validated() as $key => $newValue) {
            if (isset($oldValues[$key]) && $oldValues[$key] != $newValue) {
                $changes[$key] = [
                    'old' => $oldValues[$key],
                    'new' => $newValue,
                ];
            }
        }

        return $this->success(new GradeResource($item), 'Grade updated successfully');
    }

    public function destroy(int|string $grade): JsonResponse
    {
        DB::transaction(fn () => $this->repository->delete($grade));

        return $this->success(null, 'Grade deleted successfully', 204);
    }

    /**
     * Submit a grade for validation
     * POST /api/grades/{id}/submit
     * Changes status to SUBMITTED and prevents further editing
     */
    public function submit(int|string $grade): JsonResponse
    {
        $item = $this->repository->find($grade);

        // Validation: Cannot submit if already submitted or beyond
        if (in_array($item->status, ['SUBMITTED', 'VALIDATED', 'PUBLISHED'])) {
            return $this->error('Grade has already been submitted and cannot be modified', 422, ['status' => ['Invalid status transition']]);
        }

        // Validation: Check required fields
        if (is_null($item->score) || is_null($item->max_score)) {
            return $this->error('Grade must have a score and max_score before submission', 422, ['score' => ['Score and max_score are required']]);
        }

        // Update status to SUBMITTED
        $item = DB::transaction(fn () => $this->repository->update($grade, [
            'status' => 'SUBMITTED',
            'entered_at' => now(),
        ]));

        // TODO: Notify admin (implement notification system)
        // event(new GradeSubmitted($item));

        return $this->success(new GradeResource($item), 'Grade submitted successfully for validation');
    }

    /**
     * Validate a submitted grade (Admin/Department Head only)
     * POST /api/grades/{id}/validate
     * Changes status to VALIDATED and triggers calculation
     */
    public function validateGrade(int|string $grade): JsonResponse
    {
        // TODO: Add middleware to check if user is Admin or Department Head
        // For now, we'll just check if user is authenticated

        $item = $this->repository->find($grade);

        // Validation: Can only validate SUBMITTED grades
        if ($item->status !== 'SUBMITTED') {
            return $this->error('Only submitted grades can be validated', 422, ['status' => ['Grade must be in SUBMITTED status']]);
        }

        // Update status to VALIDATED
        $item = DB::transaction(fn () => $this->repository->update($grade, [
            'status' => 'VALIDATED',
            'validated_at' => now(),
        ]));

        // TODO: Trigger calculation logic
        // event(new GradeValidated($item));

        // TODO: Add to audit log
        // AuditLog::create([...]);

        return $this->success(new GradeResource($item), 'Grade validated successfully');
    }

    /**
     * Publish all validated grades (Admin only)
     * POST /api/grades/publish
     * Bulk operation to publish all VALIDATED grades
     */
    public function publish(Request $request): JsonResponse
    {
        // TODO: Add middleware to check if user is Admin only

        // Get all VALIDATED grades
        $validatedGrades = $this->repository->getByStatus('VALIDATED');

        if ($validatedGrades->isEmpty()) {
            return $this->error('No validated grades found to publish', 404, ['count' => 0]);
        }

        // Bulk update to PUBLISHED status
        $publishedCount = DB::transaction(fn () => $this->repository->bulkPublish($validatedGrades->pluck('id')->toArray()));

        // TODO: Notify students
        // event(new GradesPublished($validatedGrades));

        return $this->success([
            'count' => $publishedCount,
            'message' => "Successfully published {$publishedCount} grade(s)",
        ], 'Grades published successfully');
    }
}

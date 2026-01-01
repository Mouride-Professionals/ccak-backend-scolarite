<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\SemesterResultRepository;
use App\Http\Requests\SemesterResult\StoreSemesterResultRequest;
use App\Http\Requests\SemesterResult\UpdateSemesterResultRequest;
use App\Http\Resources\SemesterResultResource;
use App\Services\SemesterResultCalculationService;
use App\Jobs\CalculateSemesterResultsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SemesterResultController extends BaseApiController
{
    public function __construct(
        private readonly SemesterResultRepository $repository,
        private readonly SemesterResultCalculationService $calculationService
    ) {
        $this->middleware('permission:semester_results.view')->only(['index', 'show', 'statistics']);
        $this->middleware('permission:semester_results.create')->only('store');
        $this->middleware('permission:semester_results.update')->only('update');
        $this->middleware('permission:semester_results.delete')->only('destroy');
        $this->middleware('permission:semester_results.calculate')->only(['calculate', 'recalculateStudent']);
    }

    public function index(Request $request): JsonResponse
    {
        $results = QueryBuilder::for(\App\Models\SemesterResult::query())
            ->with(['student', 'academicYear', 'calculatedBy'])
            ->allowedIncludes(['student', 'academicYear', 'calculatedBy'])
            ->allowedFilters([
                AllowedFilter::exact('student_id'),
                AllowedFilter::exact('academic_year_id'),
                AllowedFilter::exact('semester'),
                AllowedFilter::exact('decision'),
                AllowedFilter::scope('calculated_between'),
            ])
            ->allowedSorts(['semester_average', 'semester_gpa', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success(
            SemesterResultResource::collection($results),
            'Semester results retrieved successfully'
        );
    }

    public function store(StoreSemesterResultRequest $request): JsonResponse
    {
        $item = DB::transaction(fn() => $this->repository->create($request->validated()));
        return $this->success(new SemesterResultResource($item), 'Semester result created successfully', 201);
    }

    public function show(int|string $semesterResult): JsonResponse
    {
        return $this->success(new SemesterResultResource($this->repository->find($semesterResult)), 'Semester result retrieved successfully');
    }

    public function update(UpdateSemesterResultRequest $request, int|string $semesterResult): JsonResponse
    {
        $item = DB::transaction(fn() => $this->repository->update($semesterResult, $request->validated()));
        return $this->success(new SemesterResultResource($item), 'Semester result updated successfully');
    }

    public function destroy(int|string $semesterResult): JsonResponse
    {
        DB::transaction(fn() => $this->repository->delete($semesterResult));
        return $this->success(null, 'Semester result deleted successfully', 204);
    }

    /**
     * Calculate semester results for all students
     * POST /api/semester-results/calculate
     * Admin only endpoint that queues calculation job
     */
    public function calculate(Request $request): JsonResponse
    {
        // Validate admin authorization
        $user = $request->user();
        if (!$user || !$user->hasRole('ADMIN')) {
            return $this->error('Only administrators are authorized to calculate semester results.', 403, ['authorization' => ['Admin role required']]);
        }

        // Validate request data
        $validated = $request->validate([
            'academic_year_id' => 'required|string|exists:academic_years,id',
            'semester' => 'required|integer|min:1|max:2',
            'async' => 'boolean'
        ]);

        $academicYearId = $validated['academic_year_id'];
        $semester = $validated['semester'];
        $async = $validated['async'] ?? true;

        // Generate unique job ID
        $jobId = Str::uuid()->toString();

        if ($async) {
            // Queue the calculation job
            CalculateSemesterResultsJob::dispatch(
                $academicYearId,
                $semester,
                $user->id,
                $jobId
            )->onQueue('semester-calculations');

            return $this->success([
                'job_id' => $jobId,
                'academic_year_id' => $academicYearId,
                'semester' => $semester,
                'status' => 'queued'
            ], 'Semester results calculation has been queued and will be processed asynchronously.', 202);
        } else {
            // Process synchronously (for small datasets or testing)
            try {
                $result = $this->calculationService->calculateSemesterResults(
                    $academicYearId,
                    $semester,
                    $user
                );

                if ($result['success']) {
                    return $this->success($result['data'], $result['message']);
                } else {
                    return $this->error($result['message'], 422);
                }

            } catch (\Exception $e) {
                return $this->error('Failed to calculate semester results: ' . $e->getMessage(), 500, ['calculation' => [$e->getMessage()]]);
            }
        }
    }

    /**
     * Get semester statistics
     * GET /api/semester-results/statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|string|exists:academic_years,id',
            'semester' => 'required|integer|min:1|max:2',
        ]);

        try {
            $statistics = $this->calculationService->getSemesterStatistics(
                $validated['academic_year_id'],
                (int) $validated['semester']
            );

            return $this->success($statistics, 'Semester statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve semester statistics: ' . $e->getMessage(), 500, ['statistics' => [$e->getMessage()]]);
        }
    }

    /**
     * Recalculate results for a specific student
     * POST /api/semester-results/recalculate/{student}
     */
    public function recalculateStudent(Request $request, string $studentId): JsonResponse
    {
        // Validate admin authorization
        $user = $request->user();
        if (!$user || !$user->hasRole('ADMIN')) {
            return $this->error('Only administrators are authorized to recalculate student results.', 403, ['authorization' => ['Admin role required']]);
        }

        $validated = $request->validate([
            'academic_year_id' => 'required|string|exists:academic_years,id',
            'semester' => 'required|integer|min:1|max:2',
        ]);

        try {
            $student = \App\Models\Student::findOrFail($studentId);

            $result = $this->calculationService->calculateStudentSemesterResult(
                $student,
                $validated['academic_year_id'],
                $validated['semester'],
                $user
            );

            if ($result) {
                return $this->success(new SemesterResultResource($result), 'Student semester result recalculated successfully');
            } else {
                return $this->error('Could not recalculate semester result for this student', 422, ['student' => ['No courses found or calculation failed']]);
            }

        } catch (ModelNotFoundException $e) {
            return $this->error('Student not found.', 404);
        } catch (\Exception $e) {
            return $this->error('Failed to recalculate student result: ' . $e->getMessage(), 500, ['calculation' => [$e->getMessage()]]);
        }
    }
}

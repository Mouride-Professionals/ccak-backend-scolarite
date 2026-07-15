<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\CourseEnrollment\CheckAvailabilityRequest;
use App\Http\Requests\CourseEnrollment\EnrollCourseRequest;
use App\Http\Requests\CourseEnrollment\StoreCourseEnrollmentRequest;
use App\Http\Requests\CourseEnrollment\UpdateCourseEnrollmentRequest;
use App\Http\Resources\CourseEnrollmentResource;
use App\Http\Resources\EnrollmentResource;
use App\Models\AcademicProgram;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Enrollment;
use App\Repositories\CourseEnrollmentRepository;
use App\Services\Academic\CourseEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CourseEnrollmentController extends BaseApiController
{
    public function __construct(
        private readonly CourseEnrollmentRepository $repository,
        private readonly CourseEnrollmentService $service
    ) {
        $this->middleware('permission:course_enrollments.view')->only(['index', 'show', 'getCourses', 'checkAvailability', 'getAvailableCoursesByProgram', 'getCourseEnrollmentMatrix']);
        $this->middleware('permission:course_enrollments.create')->only(['store', 'enrollCourse', 'saveCourseEnrollmentMatrix']);
        $this->middleware('permission:course_enrollments.update')->only(['update', 'dropCourse', 'saveCourseEnrollmentMatrix']);
        $this->middleware('permission:course_enrollments.delete')->only('destroy');
    }

    public function index(Request $request): JsonResponse
    {
        $courseEnrollments = QueryBuilder::for(CourseEnrollment::query())
            ->with(['enrollment.academicYear', 'course', 'student'])
            ->allowedIncludes(['enrollment', 'course', 'student', 'academicYear'])
            ->allowedFilters([
                AllowedFilter::exact('student_id'),
                AllowedFilter::exact('course_id'),
                AllowedFilter::exact('enrollment_id'),
                AllowedFilter::exact('academic_year_id'),
                AllowedFilter::exact('semester'),
                AllowedFilter::exact('status'),
                AllowedFilter::scope('enrollment_date_between'),
                AllowedFilter::scope('drop_date_between'),
            ])
            ->allowedSorts(['created_at', 'semester', 'enrollment_date'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success(CourseEnrollmentResource::collection($courseEnrollments));
    }

    public function store(StoreCourseEnrollmentRequest $request): JsonResponse
    {
        $item = DB::transaction(fn () => $this->repository->create($request->validated()));

        return $this->success(new CourseEnrollmentResource($item), 'Course enrollment created', 201);
    }

    public function show(int|string $courseEnrollment): JsonResponse
    {
        return $this->success(new CourseEnrollmentResource($this->repository->find($courseEnrollment)));
    }

    public function update(UpdateCourseEnrollmentRequest $request, int|string $courseEnrollment): JsonResponse
    {
        $item = DB::transaction(fn () => $this->repository->update($courseEnrollment, $request->validated()));

        return $this->success(new CourseEnrollmentResource($item), 'Course enrollment updated');
    }

    public function destroy(Request $request, CourseEnrollment $course_enrollment): JsonResponse
    {
        $course_enrollment->load(['enrollment.academicYear', 'course']);

        $result = $this->service->dropCourse($course_enrollment, $request->user());
        if (isset($result['error'])) {
            return $this->error(
                $result['error']['message'],
                $result['error']['status'],
                $result['error']['errors'] ?? []
            );
        }

        return $this->success([
            'course_enrollment' => new CourseEnrollmentResource($result['course_enrollment']),
            'admin_override' => $result['admin_override'],
        ], 'Cours abandonné avec succès.');
    }

    /**
     * Enroll a student in a course
     */
    public function enrollCourse(EnrollCourseRequest $request, Enrollment $enrollment): JsonResponse
    {
        $result = $this->service->enrollCourse($enrollment, $request->validated());
        if (isset($result['error'])) {
            return $this->error(
                $result['error']['message'],
                $result['error']['status'],
                $result['error']['errors'] ?? []
            );
        }

        return $this->success([
            'course_enrollment' => new CourseEnrollmentResource($result['course_enrollment']),
            'seat_info' => $result['seat_info'],
        ], 'Inscription au cours réussie.', 201);
    }

    /**
     * Get all course enrollments for an enrollment
     */
    public function getCourses(Enrollment $enrollment): JsonResponse
    {
        $result = $this->service->getCoursesForEnrollment($enrollment);

        return $this->success([
            'enrollment' => new EnrollmentResource($result['enrollment']),
            'course_enrollments' => CourseEnrollmentResource::collection($result['course_enrollments']),
            'total_courses' => $result['total_courses'],
            'active_courses' => $result['active_courses'],
        ]);
    }

    /**
     * Drop a course (change status to DROPPED)
     */
    public function dropCourse(Enrollment $enrollment, CourseEnrollment $courseEnrollment, Request $request): JsonResponse
    {
        if ($courseEnrollment->enrollment_id !== $enrollment->id) {
            return $this->error('Inscription au cours non trouvée.', 404);
        }

        $result = $this->service->dropCourse($courseEnrollment, $request->user());
        if (isset($result['error'])) {
            return $this->error(
                $result['error']['message'],
                $result['error']['status'],
                $result['error']['errors'] ?? []
            );
        }

        return $this->success(
            new CourseEnrollmentResource($result['course_enrollment']),
            'Cours abandonné avec succès.'
        );
    }

    /**
     * Check seat availability for a course
     */
    public function checkAvailability(Course $course, CheckAvailabilityRequest $request): JsonResponse
    {
        $result = $this->service->checkAvailability($course, $request->validated());

        return $this->success($result);
    }

    /**
     * Get available courses for a program
     */
    public function getAvailableCoursesByProgram(Request $request, AcademicProgram $program): JsonResponse
    {
        $result = $this->service->getAvailableCoursesByProgram($program, $request->only([
            'academic_year_id',
            'semester',
            'student_id',
            'search',
        ]));

        if (isset($result['error'])) {
            return $this->error(
                $result['error']['message'],
                $result['error']['status'],
                $result['error']['errors'] ?? []
            );
        }

        return $this->success($result);
    }

    public function getCourseEnrollmentMatrix(Request $request, AcademicProgram $program): JsonResponse
    {
        $payload = $request->validate([
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'semester' => ['required', 'integer', 'min:1', 'max:12'],
            'status' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->service->getCourseEnrollmentMatrix($program, $payload);
        if (isset($result['error'])) {
            return $this->error(
                $result['error']['message'],
                $result['error']['status'],
                $result['error']['errors'] ?? []
            );
        }

        return $this->success($result);
    }

    public function saveCourseEnrollmentMatrix(Request $request, AcademicProgram $program): JsonResponse
    {
        $payload = $request->validate([
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'semester' => ['required', 'integer', 'min:1', 'max:12'],
            'enrollment_date' => ['nullable', 'date'],
            'creates' => ['array'],
            'creates.*.enrollment_id' => ['required_with:creates', 'uuid', 'exists:enrollments,id'],
            'creates.*.course_id' => ['required_with:creates', 'uuid', 'exists:courses,id'],
            'drops' => ['array'],
            'drops.*.course_enrollment_id' => ['required_with:drops', 'uuid', 'exists:course_enrollments,id'],
        ]);

        $result = $this->service->saveCourseEnrollmentMatrix($program, $payload);
        if (isset($result['error'])) {
            return $this->error(
                $result['error']['message'],
                $result['error']['status'],
                $result['error']['errors'] ?? []
            );
        }

        return $this->success($result);
    }
}

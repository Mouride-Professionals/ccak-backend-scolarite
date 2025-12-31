<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Repositories\CourseEnrollmentRepository;
use App\Http\Requests\CourseEnrollment\StoreCourseEnrollmentRequest;
use App\Http\Requests\CourseEnrollment\UpdateCourseEnrollmentRequest;
use App\Http\Resources\CourseEnrollmentResource;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CourseEnrollmentController extends BaseApiController
{
    public function __construct(private readonly CourseEnrollmentRepository $repository)
    {
        $this->middleware('permission:course_enrollments.view')->only(['index', 'show', 'getCourses', 'checkAvailability', 'getAvailableCoursesByProgram']);
        $this->middleware('permission:course_enrollments.create')->only(['store', 'enrollCourse']);
        $this->middleware('permission:course_enrollments.update')->only(['update', 'dropCourse']);
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
            ])
            ->allowedSorts(['created_at', 'semester', 'enrollment_date'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success($courseEnrollments);
    }

    public function store(StoreCourseEnrollmentRequest $request): JsonResponse
    {
        $item = DB::transaction(fn() => $this->repository->create($request->validated()));
        return response()->json(new CourseEnrollmentResource($item), 201);
    }

    public function show(int|string $courseEnrollment): JsonResponse
    {
        return response()->json(new CourseEnrollmentResource($this->repository->find($courseEnrollment)));
    }

    public function update(UpdateCourseEnrollmentRequest $request, int|string $courseEnrollment): JsonResponse
    {
        $item = DB::transaction(fn() => $this->repository->update($courseEnrollment, $request->validated()));
        return response()->json(new CourseEnrollmentResource($item));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $courseEnrollment = CourseEnrollment::with([
            'enrollment.academicYear',
            'course'
        ])->find($id);

        if (!$courseEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription au cours non trouvée.',
            ], 404);
        }

        // Check if already dropped or completed
        if ($courseEnrollment->status !== 'ENROLLED') {
            return response()->json([
                'success' => false,
                'message' => 'Le cours ne peut être abandonné. Statut actuel: ' . $courseEnrollment->status,
            ], 422);
        }

        // Check drop deadline (unless admin override)
        $isAdmin = $request->user()?->hasRole('ADMIN') ?? false;

        if (!$isAdmin) {
            $dropDeadline = $this->calculateDropDeadline($courseEnrollment);

            if (Carbon::now()->isAfter($dropDeadline)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La date limite d\'abandon est dépassée.',
                    'drop_deadline' => $dropDeadline->format('Y-m-d'),
                ], 422);
            }
        }

        // Update status to DROPPED (don't delete the record)
        DB::transaction(function () use ($courseEnrollment) {
            $courseEnrollment->update([
                'status' => 'DROPPED',
                'drop_date' => Carbon::now(),
            ]);
        });

        // Create audit log
        $this->createAuditLog($courseEnrollment, $request->user(), $isAdmin);

        return response()->json([
            'success' => true,
            'message' => 'Cours abandonné avec succès.',
            'data' => [
                'id' => $courseEnrollment->id,
                'course' => [
                    'code' => $courseEnrollment->course->code,
                    'name' => $courseEnrollment->course->name,
                ],
                'status' => $courseEnrollment->status,
                'drop_date' => $courseEnrollment->drop_date->format('Y-m-d'),
                'admin_override' => $isAdmin,
            ],
        ]);
    }

    /**
     * Enroll a student in a course
     */
    public function enrollCourse(Request $request, string $enrollmentId): JsonResponse
    {
        // Find enrollment
        $enrollment = Enrollment::with(['student', 'academicProgram', 'academicYear'])->find($enrollmentId);

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription non trouvée.',
            ], 404);
        }

        // Check if enrollment is active
        if (!$enrollment->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'L\'inscription n\'est pas active. Statut actuel: ' . $enrollment->status,
            ], 422);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|uuid|exists:courses,id',
            'semester' => 'required|integer|min:1|max:12',
            'enrollment_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get course
        $course = Course::find($request->course_id);
        if (!$course || !$course->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cours non trouvé ou inactif.',
            ], 404);
        }

        // Check if course belongs to the program (if you have this relationship)
        // This would require a pivot table or course_program relationship
        // For now, we'll skip this check

        // Check for duplicates
        if (CourseEnrollment::isDuplicate($enrollmentId, $request->course_id, $enrollment->academic_year_id)) {
            return response()->json([
                'success' => false,
                'message' => 'L\'étudiant est déjà inscrit à ce cours pour cette année académique.',
            ], 422);
        }

        // Check prerequisites
        $prerequisiteCheck = CourseEnrollment::checkPrerequisites($enrollment->student_id, $request->course_id);
        if (!$prerequisiteCheck['satisfied']) {
            $missingCourses = Course::whereIn('id', $prerequisiteCheck['missing'])->pluck('name')->toArray();

            return response()->json([
                'success' => false,
                'message' => 'Prérequis non satisfaits.',
                'missing_prerequisites' => $missingCourses,
            ], 422);
        }

        // Check seat availability
        $availabilityCheck = CourseEnrollment::checkSeatAvailability(
            $request->course_id,
            $enrollment->academic_year_id,
            $request->semester
        );

        if (!$availabilityCheck['available']) {
            return response()->json([
                'success' => false,
                'message' => 'Plus de places disponibles pour ce cours.',
                'availability' => $availabilityCheck,
            ], 422);
        }

        // Create course enrollment with transaction
        DB::beginTransaction();
        try {
            $courseEnrollment = CourseEnrollment::create([
                'enrollment_id' => $enrollmentId,
                'course_id' => $request->course_id,
                'academic_year_id' => $enrollment->academic_year_id,
                'semester' => $request->semester,
                'status' => CourseEnrollment::STATUS_ENROLLED,
                'enrollment_date' => $request->enrollment_date ?? now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inscription au cours réussie.',
                'data' => [
                    'course_enrollment_id' => $courseEnrollment->id,
                    'course_enrollment' => $courseEnrollment->load(['course', 'enrollment.student']),
                    'seat_info' => CourseEnrollment::checkSeatAvailability(
                        $request->course_id,
                        $enrollment->academic_year_id,
                        $request->semester
                    ),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription au cours.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Get all course enrollments for an enrollment
     */
    public function getCourses(string $enrollmentId): JsonResponse
    {
        $enrollment = Enrollment::find($enrollmentId);

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription non trouvée.',
            ], 404);
        }

        $courseEnrollments = $enrollment->courseEnrollments()
            ->with('course')
            ->orderBy('semester')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'enrollment' => $enrollment->load(['student', 'academicProgram', 'academicYear']),
                'course_enrollments' => $courseEnrollments,
                'total_courses' => $courseEnrollments->count(),
                'active_courses' => $courseEnrollments->where('status', CourseEnrollment::STATUS_ENROLLED)->count(),
            ],
        ]);
    }

    /**
     * Drop a course (change status to DROPPED)
    */
    public function dropCourse(string $enrollmentId, string $courseEnrollmentId): JsonResponse
    {
        $courseEnrollment = CourseEnrollment::where('id', $courseEnrollmentId)
            ->where('enrollment_id', $enrollmentId)
            ->first();

        if (!$courseEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription au cours non trouvée.',
            ], 404);
        }

        if ($courseEnrollment->status !== CourseEnrollment::STATUS_ENROLLED) {
            return response()->json([
                'success' => false,
                'message' => 'Le cours ne peut être abandonné. Statut actuel: ' . $courseEnrollment->status,
            ], 422);
        }

        DB::transaction(function () use ($courseEnrollment) {
            $courseEnrollment->update([
                'status' => CourseEnrollment::STATUS_DROPPED,
                'drop_date' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Cours abandonné avec succès.',
            'data' => $courseEnrollment->fresh(['course']),
        ]);
    }

    /**
     * Check seat availability for a course
     */
    public function checkAvailability(string $courseId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'semester' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $course = Course::find($courseId);
        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Cours non trouvé.',
            ], 404);
        }

        $availability = CourseEnrollment::checkSeatAvailability(
            $courseId,
            $request->academic_year_id,
            $request->semester
        );

        return response()->json([
            'success' => true,
            'data' => [
                'course' => $course,
                'availability' => $availability,
            ],
        ]);
    }

    /**
     * Calculate drop deadline based on enrollment date
     * Usually 2-4 weeks after semester start
     */
    private function calculateDropDeadline(CourseEnrollment $courseEnrollment): Carbon
    {
        // Get academic year start date
        $academicYear = $courseEnrollment->enrollment->academicYear;

        // Calculate semester start based on academic year and semester number
        $semesterStart = Carbon::parse($academicYear->start_date);

        // If semester 2, add ~6 months
        if ($courseEnrollment->semester > 1) {
            $semesterStart->addMonths(($courseEnrollment->semester - 1) * 6);
        }

        // Drop deadline is 3 weeks after semester start
        return $semesterStart->copy()->addWeeks(3);
    }

    /**
     * Get available courses for a program
    */
    public function getAvailableCoursesByProgram(Request $request, string $programId): JsonResponse
    {
        $program = AcademicProgram::find($programId);

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'Programme académique non trouvé.',
            ], 404);
        }

        // Get current academic year or specific year
        $academicYearId = $request->input('academic_year_id');
        if (!$academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear?->id;
        }

        if (!$academicYearId) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune année académique trouvée.',
            ], 404);
        }

        // Get current semester or from request
        $semester = $request->input('semester', 1);

        // Get student ID if filtering for a specific student
        $studentId = $request->input('student_id');

        // Build query for courses
        $query = Course::where('is_active', true);

        // Filter by search term
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $courses = $query->get();

        // Get completed courses for the student (if provided)
        $completedCourseIds = [];
        if ($studentId) {
            $completedCourseIds = CourseEnrollment::whereHas('enrollment', function($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                })
                ->where('status', 'COMPLETED')
                ->pluck('course_id')
                ->toArray();
        }

        // Format courses with additional information
        $availableCourses = $courses->map(function ($course) use ($academicYearId, $semester, $completedCourseIds, $studentId) {
            // Check if already completed
            $isCompleted = in_array($course->id, $completedCourseIds);

            // Skip completed courses
            if ($isCompleted) {
                return null;
            }

            // Get enrollment count
            $enrollmentCount = CourseEnrollment::where('course_id', $course->id)
                ->where('academic_year_id', $academicYearId)
                ->where('semester', $semester)
                ->where('status', 'ENROLLED')
                ->count();

            $maxCapacity = 50; // Default capacity
            $seatsAvailable = max(0, $maxCapacity - $enrollmentCount);

            // Check prerequisites status
            $prerequisites = is_array($course->prerequisites)
                ? $course->prerequisites
                : (json_decode($course->prerequisites, true) ?? []);

            $prerequisiteStatus = $this->checkPrerequisiteStatus($prerequisites, $completedCourseIds);

            // Check if student is already enrolled (if student_id provided)
            $isEnrolled = false;
            if ($studentId) {
                $isEnrolled = CourseEnrollment::whereHas('enrollment', function($query) use ($studentId) {
                        $query->where('student_id', $studentId);
                    })
                    ->where('course_id', $course->id)
                    ->where('academic_year_id', $academicYearId)
                    ->where('semester', $semester)
                    ->where('status', 'ENROLLED')
                    ->exists();
            }

            return [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'description' => $course->description,
                'credits' => $course->credits,
                'hours_lecture' => $course->hours_lecture,
                'hours_td' => $course->hours_td,
                'hours_tp' => $course->hours_tp,
                'coefficient' => $course->coefficient,
                'seat_availability' => [
                    'enrolled' => $enrollmentCount,
                    'max_capacity' => $maxCapacity,
                    'available' => $seatsAvailable,
                    'percentage_full' => $maxCapacity > 0 ? round(($enrollmentCount / $maxCapacity) * 100, 2) : 0,
                ],
                'prerequisites' => [
                    'required' => $prerequisites,
                    'satisfied' => $prerequisiteStatus['satisfied'],
                    'missing' => $prerequisiteStatus['missing'],
                    'missing_course_details' => $prerequisiteStatus['missing_details'],
                ],
                'enrollment_status' => [
                    'is_enrolled' => $isEnrolled,
                    'is_completed' => $isCompleted,
                    'can_enroll' => !$isEnrolled && !$isCompleted && $seatsAvailable > 0 && $prerequisiteStatus['satisfied'],
                ],
            ];
        })->filter()->values(); // Remove null values (completed courses)

        return response()->json([
            'success' => true,
            'data' => [
                'program' => [
                    'id' => $program->id,
                    'name' => $program->name,
                    'level' => $program->level,
                ],
                'academic_year_id' => $academicYearId,
                'semester' => $semester,
                'courses' => $availableCourses,
                'total_available' => $availableCourses->count(),
                'filters_applied' => [
                    'search' => $request->input('search'),
                    'student_id' => $studentId,
                ],
            ],
        ]);
    }

    /**
     * Check prerequisite status for a course
    */
    private function checkPrerequisiteStatus(array $prerequisites, array $completedCourseIds): array
    {
        if (empty($prerequisites)) {
            return [
                'satisfied' => true,
                'missing' => [],
                'missing_details' => [],
            ];
        }

        $missingPrerequisites = array_diff($prerequisites, $completedCourseIds);

        $missingDetails = [];
        if (!empty($missingPrerequisites)) {
            $missingCourses = Course::whereIn('id', $missingPrerequisites)->get();
            $missingDetails = $missingCourses->map(function($course) {
                return [
                    'id' => $course->id,
                    'code' => $course->code,
                    'name' => $course->name,
                ];
            })->toArray();
        }

        return [
            'satisfied' => empty($missingPrerequisites),
            'missing' => array_values($missingPrerequisites),
            'missing_details' => $missingDetails,
        ];
    }

    private function createAuditLog(CourseEnrollment $courseEnrollment, $user, bool $adminOverride): void
    {
        Log::info('Course enrollment dropped', [
            'course_enrollment_id' => $courseEnrollment->id,
            'enrollment_id' => $courseEnrollment->enrollment_id,
            'course_id' => $courseEnrollment->course_id,
            'student_id' => $courseEnrollment->enrollment?->student_id,
            'admin_override' => $adminOverride,
            'user_id' => $user?->id,
        ]);
    }
}

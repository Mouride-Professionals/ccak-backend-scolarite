<?php

namespace App\Services\Academic;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseEnrollmentService
{
    public function __construct(
        private readonly EnrollmentValidationService $validationService
    ) {
    }

    public function enrollCourse(Enrollment $enrollment, array $payload): array
    {
        if (!$enrollment->isActive()) {
            return [
                'error' => [
                    'message' => 'L\'inscription n\'est pas active. Statut actuel: ' . $enrollment->status,
                    'status' => 422,
                ],
            ];
        }

        $course = Course::find($payload['course_id']);
        if (!$course || !$course->is_active) {
            return [
                'error' => [
                    'message' => 'Cours non trouvé ou inactif.',
                    'status' => 404,
                ],
            ];
        }

        if (CourseEnrollment::isDuplicate($enrollment->id, $payload['course_id'], $enrollment->academic_year_id)) {
            return [
                'error' => [
                    'message' => 'L\'étudiant est déjà inscrit à ce cours pour cette année académique.',
                    'status' => 422,
                ],
            ];
        }

        $prerequisiteCheck = $this->validationService->validatePrerequisites($enrollment->student_id, $course);
        if (!$prerequisiteCheck['satisfied']) {
            return [
                'error' => [
                    'message' => 'Prérequis non satisfaits.',
                    'status' => 422,
                    'errors' => [
                        'missing_required_prerequisites' => $prerequisiteCheck['missing_required_courses'],
                        'missing_optional_prerequisites' => $prerequisiteCheck['missing_optional_courses'],
                    ],
                ],
            ];
        }

        $availabilityCheck = CourseEnrollment::checkSeatAvailability(
            $payload['course_id'],
            $enrollment->academic_year_id,
            $payload['semester']
        );

        if (!$availabilityCheck['available']) {
            return [
                'error' => [
                    'message' => 'Plus de places disponibles pour ce cours.',
                    'status' => 422,
                    'errors' => [
                        'availability' => $availabilityCheck,
                    ],
                ],
            ];
        }

        $courseEnrollment = DB::transaction(function () use ($enrollment, $payload) {
            return CourseEnrollment::create([
                'enrollment_id' => $enrollment->id,
                'course_id' => $payload['course_id'],
                'academic_year_id' => $enrollment->academic_year_id,
                'semester' => $payload['semester'],
                'status' => CourseEnrollment::STATUS_ENROLLED,
                'enrollment_date' => $payload['enrollment_date'] ?? now(),
            ]);
        });

        return [
            'course_enrollment' => $courseEnrollment->load(['course', 'enrollment.student']),
            'seat_info' => CourseEnrollment::checkSeatAvailability(
                $payload['course_id'],
                $enrollment->academic_year_id,
                $payload['semester']
            ),
        ];
    }

    public function getCoursesForEnrollment(Enrollment $enrollment): array
    {
        $courseEnrollments = $enrollment->courseEnrollments()
            ->with('course')
            ->orderBy('semester')
            ->orderBy('created_at')
            ->get();

        return [
            'enrollment' => $enrollment->load(['student', 'academicProgram', 'academicYear']),
            'course_enrollments' => $courseEnrollments,
            'total_courses' => $courseEnrollments->count(),
            'active_courses' => $courseEnrollments->where('status', CourseEnrollment::STATUS_ENROLLED)->count(),
        ];
    }

    public function dropCourse(CourseEnrollment $courseEnrollment, ?Authenticatable $user): array
    {
        if ($courseEnrollment->status !== CourseEnrollment::STATUS_ENROLLED) {
            return [
                'error' => [
                    'message' => 'Le cours ne peut être abandonné. Statut actuel: ' . $courseEnrollment->status,
                    'status' => 422,
                ],
            ];
        }

        $isAdmin = $user?->hasRole('ADMIN') ?? false;
        if (!$isAdmin) {
            $dropDeadline = $this->calculateDropDeadline($courseEnrollment);

            if (Carbon::now()->isAfter($dropDeadline)) {
                return [
                    'error' => [
                        'message' => 'La date limite d\'abandon est dépassée.',
                        'status' => 422,
                        'errors' => [
                            'drop_deadline' => $dropDeadline->format('Y-m-d'),
                        ],
                    ],
                ];
            }
        }

        DB::transaction(function () use ($courseEnrollment) {
            $courseEnrollment->update([
                'status' => CourseEnrollment::STATUS_DROPPED,
                'drop_date' => now(),
            ]);
        });

        $courseEnrollment->refresh(['course']);
        $this->createAuditLog($courseEnrollment, $user, $isAdmin);

        return [
            'course_enrollment' => $courseEnrollment,
            'admin_override' => $isAdmin,
        ];
    }

    public function checkAvailability(Course $course, array $payload): array
    {
        $availability = CourseEnrollment::checkSeatAvailability(
            $course->id,
            $payload['academic_year_id'],
            $payload['semester']
        );

        return [
            'course' => $course,
            'availability' => $availability,
        ];
    }

    public function getAvailableCoursesByProgram(AcademicProgram $program, array $payload): array
    {
        $academicYearId = $payload['academic_year_id'] ?? null;
        if (!$academicYearId) {
            $academicYearId = AcademicYear::where('is_current', true)->value('id');
        }

        if (!$academicYearId) {
            return [
                'error' => [
                    'message' => 'Aucune année académique trouvée.',
                    'status' => 404,
                ],
            ];
        }

        $semester = (int) ($payload['semester'] ?? 1);
        $studentId = $payload['student_id'] ?? null;
        $search = $payload['search'] ?? null;

        $query = Course::query()->active();
        if ($search) {
            $query->search($search);
        }

        $courses = $query->get();

        $completedCourseIds = [];
        if ($studentId) {
            $completedCourseIds = CourseEnrollment::whereHas('enrollment', function ($query) use ($studentId) {
                    $query->where('student_id', $studentId);
                })
                ->where('status', CourseEnrollment::STATUS_COMPLETED)
                ->pluck('course_id')
                ->toArray();
        }

        $availableCourses = $courses->map(function ($course) use ($academicYearId, $semester, $completedCourseIds, $studentId) {
            $isCompleted = in_array($course->id, $completedCourseIds, true);
            if ($isCompleted) {
                return null;
            }

            $enrollmentCount = CourseEnrollment::where('course_id', $course->id)
                ->where('academic_year_id', $academicYearId)
                ->where('semester', $semester)
                ->where('status', CourseEnrollment::STATUS_ENROLLED)
                ->count();

            $maxCapacity = $course->max_students ?? 50;
            $seatsAvailable = max(0, $maxCapacity - $enrollmentCount);

            $prerequisites = is_array($course->prerequisites)
                ? $course->prerequisites
                : (json_decode($course->prerequisites, true) ?? []);

            $prerequisiteStatus = $this->checkPrerequisiteStatus($prerequisites, $completedCourseIds);

            $isEnrolled = false;
            if ($studentId) {
                $isEnrolled = CourseEnrollment::whereHas('enrollment', function ($query) use ($studentId) {
                        $query->where('student_id', $studentId);
                    })
                    ->where('course_id', $course->id)
                    ->where('academic_year_id', $academicYearId)
                    ->where('semester', $semester)
                    ->where('status', CourseEnrollment::STATUS_ENROLLED)
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
        })->filter()->values()->all();

        return [
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
                'search' => $search,
                'student_id' => $studentId,
            ],
        ];
    }

    private function calculateDropDeadline(CourseEnrollment $courseEnrollment): Carbon
    {
        $academicYear = $courseEnrollment->enrollment->academicYear;
        $semesterStart = Carbon::parse($academicYear->start_date);

        if ($courseEnrollment->semester > 1) {
            $semesterStart->addMonths(($courseEnrollment->semester - 1) * 6);
        }

        return $semesterStart->copy()->addWeeks(3);
    }

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
            $missingDetails = $missingCourses->map(function ($course) {
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

    private function createAuditLog(CourseEnrollment $courseEnrollment, ?Authenticatable $user, bool $adminOverride): void
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

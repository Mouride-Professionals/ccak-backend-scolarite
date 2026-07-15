<?php

namespace App\Services\Academic;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\DeliberationSession;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseEnrollmentService
{
    public function __construct(
        private readonly EnrollmentValidationService $validationService
    ) {}

    public function enrollCourse(Enrollment $enrollment, array $payload): array
    {
        if (! $enrollment->isActive()) {
            return [
                'error' => [
                    'message' => 'L\'inscription n\'est pas active. Statut actuel: '.$enrollment->status->value,
                    'status' => 422,
                ],
            ];
        }

        $course = Course::find($payload['course_id']);
        if (! $course || ! $course->is_active) {
            return [
                'error' => [
                    'message' => 'Cours non trouvé ou inactif.',
                    'status' => 404,
                ],
            ];
        }

        $course->loadMissing('courseUnit');
        if (
            ! $course->courseUnit ||
            $course->courseUnit->academic_program_id !== $enrollment->academic_program_id ||
            (int) $course->courseUnit->semester_number !== (int) $payload['semester']
        ) {
            return [
                'error' => [
                    'message' => 'Ce cours ne correspond pas au programme ou au semestre de l\'inscription.',
                    'status' => 422,
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
        if (! $prerequisiteCheck['satisfied']) {
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

        if (! $availabilityCheck['available']) {
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
                'student_id' => $enrollment->student_id,
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
                    'message' => 'Le cours ne peut être abandonné. Statut actuel: '.$courseEnrollment->status,
                    'status' => 422,
                ],
            ];
        }

        $isAdmin = $user instanceof User && $user->hasRole('ADMIN');
        if (! $isAdmin) {
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

        $courseEnrollment->refresh();
        $courseEnrollment->load('course');
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
        if (! $academicYearId) {
            $academicYearId = AcademicYear::where('is_current', true)->value('id');
        }

        if (! $academicYearId) {
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
        $query->whereHas('courseUnit', function ($query) use ($program, $semester) {
            $query->where('academic_program_id', $program->id)
                ->where('semester_number', $semester);
        })->with('courseUnit');

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
                    'can_enroll' => ! $isEnrolled && ! $isCompleted && $seatsAvailable > 0 && $prerequisiteStatus['satisfied'],
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
            'total_available' => count($availableCourses),
            'filters_applied' => [
                'search' => $search,
                'student_id' => $studentId,
            ],
        ];
    }

    public function getCourseEnrollmentMatrix(AcademicProgram $program, array $payload): array
    {
        $academicYear = AcademicYear::find($payload['academic_year_id']);
        if (! $academicYear) {
            return [
                'error' => [
                    'message' => 'Année académique non trouvée.',
                    'status' => 404,
                ],
            ];
        }

        $semester = (int) $payload['semester'];
        $status = $payload['status'] ?? null;
        $search = trim((string) ($payload['search'] ?? ''));

        $courses = Course::query()
            ->active()
            ->with('courseUnit')
            ->whereHas('courseUnit', function ($query) use ($program, $semester) {
                $query->where('academic_program_id', $program->id)
                    ->where('semester_number', $semester);
            })
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        $enrollmentsQuery = Enrollment::query()
            ->with(['student', 'academicProgram', 'academicYear'])
            ->where('academic_program_id', $program->id)
            ->where('academic_year_id', $academicYear->id)
            ->orderBy('created_at');

        if ($status) {
            $enrollmentsQuery->where('status', $status);
        }

        if ($search !== '') {
            $likeOperator = $enrollmentsQuery->getModel()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $enrollmentsQuery->whereHas('student', function ($query) use ($search, $likeOperator) {
                $query->where('full_name', $likeOperator, "%{$search}%")
                    ->orWhere('student_number', $likeOperator, "%{$search}%");
            });
        }

        $enrollments = $enrollmentsQuery->get();

        $courseIds = $courses->pluck('id')->all();
        $enrollmentIds = $enrollments->pluck('id')->all();

        $courseEnrollments = CourseEnrollment::query()
            ->whereIn('enrollment_id', $enrollmentIds)
            ->whereIn('course_id', $courseIds)
            ->where('academic_year_id', $academicYear->id)
            ->where('semester', $semester)
            ->get()
            ->keyBy(fn (CourseEnrollment $courseEnrollment) => $courseEnrollment->enrollment_id.'|'.$courseEnrollment->course_id);

        $courseEnrollmentIds = $courseEnrollments->pluck('id')->all();
        $gradeCounts = empty($courseEnrollmentIds)
            ? collect()
            : Grade::query()
                ->selectRaw('course_enrollment_id, count(*) as aggregate')
                ->whereIn('course_enrollment_id', $courseEnrollmentIds)
                ->groupBy('course_enrollment_id')
                ->pluck('aggregate', 'course_enrollment_id');

        $deliberationLocked = $this->isSemesterDeliberationLocked($program->id, $academicYear->id, $semester);

        $cells = [];
        foreach ($enrollments as $enrollment) {
            foreach ($courses as $course) {
                $key = $enrollment->id.'|'.$course->id;
                $courseEnrollment = $courseEnrollments->get($key);
                $gradeCount = $courseEnrollment ? (int) ($gradeCounts[$courseEnrollment->id] ?? 0) : 0;
                $lockReason = $this->courseEnrollmentLockReason($courseEnrollment, $academicYear, $deliberationLocked, $gradeCount);

                $cells[] = [
                    'enrollment_id' => $enrollment->id,
                    'course_id' => $course->id,
                    'course_enrollment_id' => $courseEnrollment?->id,
                    'checked' => $courseEnrollment && in_array($courseEnrollment->status, [
                        CourseEnrollment::STATUS_ENROLLED,
                        CourseEnrollment::STATUS_COMPLETED,
                    ], true),
                    'status' => $courseEnrollment?->status,
                    'locked' => $lockReason !== null,
                    'lock_reason' => $lockReason,
                    'has_grades' => $gradeCount > 0,
                ];
            }
        }

        return [
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'level' => $program->level,
            ],
            'academic_year' => [
                'id' => $academicYear->id,
                'name' => $academicYear->name,
                'status' => $academicYear->status,
                'is_current' => $academicYear->is_current,
            ],
            'semester' => $semester,
            'is_read_only' => $this->isAcademicYearLocked($academicYear) || $deliberationLocked,
            'read_only_reason' => $this->isAcademicYearLocked($academicYear)
                ? 'Année académique non modifiable.'
                : ($deliberationLocked ? 'Délibération clôturée pour ce semestre.' : null),
            'courses' => $courses->map(fn (Course $course) => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'credits' => $course->credits,
                'semester' => $course->courseUnit?->semester_number,
                'course_unit_id' => $course->course_unit_id,
                'course_unit_code' => $course->courseUnit?->code,
                'course_unit_name' => $course->courseUnit?->name,
            ])->values()->all(),
            'enrollments' => $enrollments->map(fn (Enrollment $enrollment) => [
                'id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'student_number' => $enrollment->student?->student_number,
                'student_name' => $enrollment->student?->full_name,
                'status' => $enrollment->status instanceof \BackedEnum ? $enrollment->status->value : (string) $enrollment->status,
            ])->values()->all(),
            'cells' => $cells,
        ];
    }

    public function saveCourseEnrollmentMatrix(AcademicProgram $program, array $payload): array
    {
        $academicYear = AcademicYear::find($payload['academic_year_id']);
        if (! $academicYear) {
            return [
                'error' => [
                    'message' => 'Année académique non trouvée.',
                    'status' => 404,
                ],
            ];
        }

        $semester = (int) $payload['semester'];
        if ($this->isAcademicYearLocked($academicYear)) {
            return [
                'error' => [
                    'message' => 'Année académique non modifiable.',
                    'status' => 422,
                ],
            ];
        }

        if ($this->isSemesterDeliberationLocked($program->id, $academicYear->id, $semester)) {
            return [
                'error' => [
                    'message' => 'Délibération clôturée pour ce semestre.',
                    'status' => 422,
                ],
            ];
        }

        $validCourseIds = Course::query()
            ->whereHas('courseUnit', function ($query) use ($program, $semester) {
                $query->where('academic_program_id', $program->id)
                    ->where('semester_number', $semester);
            })
            ->pluck('id')
            ->all();

        $validEnrollmentIds = Enrollment::query()
            ->where('academic_program_id', $program->id)
            ->where('academic_year_id', $academicYear->id)
            ->pluck('id')
            ->all();

        $validCourseSet = array_flip($validCourseIds);
        $validEnrollmentSet = array_flip($validEnrollmentIds);

        $created = 0;
        $reactivated = 0;
        $dropped = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use (
            $payload,
            $academicYear,
            $semester,
            $validCourseSet,
            $validEnrollmentSet,
            &$created,
            &$reactivated,
            &$dropped,
            &$skipped,
            &$errors
        ) {
            foreach ($payload['creates'] ?? [] as $index => $item) {
                $enrollmentId = $item['enrollment_id'] ?? null;
                $courseId = $item['course_id'] ?? null;

                if (! $enrollmentId || ! $courseId || ! isset($validEnrollmentSet[$enrollmentId], $validCourseSet[$courseId])) {
                    $errors[] = ['index' => $index, 'action' => 'create', 'message' => 'Inscription ou cours invalide.'];

                    continue;
                }

                $enrollment = Enrollment::find($enrollmentId);
                if (! $enrollment) {
                    $errors[] = ['index' => $index, 'action' => 'create', 'message' => 'Inscription introuvable.'];

                    continue;
                }

                $courseEnrollment = CourseEnrollment::query()
                    ->where('enrollment_id', $enrollmentId)
                    ->where('course_id', $courseId)
                    ->where('academic_year_id', $academicYear->id)
                    ->first();

                if ($courseEnrollment) {
                    $gradeCount = Grade::query()->where('course_enrollment_id', $courseEnrollment->id)->count();
                    $lockReason = $this->courseEnrollmentLockReason($courseEnrollment, $academicYear, false, $gradeCount);
                    if ($lockReason) {
                        $errors[] = ['index' => $index, 'action' => 'create', 'message' => $lockReason];

                        continue;
                    }

                    if ($courseEnrollment->status === CourseEnrollment::STATUS_ENROLLED) {
                        $skipped++;

                        continue;
                    }

                    $courseEnrollment->update([
                        'status' => CourseEnrollment::STATUS_ENROLLED,
                        'semester' => $semester,
                        'drop_date' => null,
                        'enrollment_date' => $payload['enrollment_date'] ?? now()->toDateString(),
                    ]);
                    $reactivated++;

                    continue;
                }

                CourseEnrollment::create([
                    'student_id' => $enrollment->student_id,
                    'enrollment_id' => $enrollment->id,
                    'course_id' => $courseId,
                    'academic_year_id' => $academicYear->id,
                    'semester' => $semester,
                    'status' => CourseEnrollment::STATUS_ENROLLED,
                    'enrollment_date' => $payload['enrollment_date'] ?? now()->toDateString(),
                ]);
                $created++;
            }

            foreach ($payload['drops'] ?? [] as $index => $item) {
                $courseEnrollmentId = $item['course_enrollment_id'] ?? null;
                $courseEnrollment = $courseEnrollmentId ? CourseEnrollment::with(['enrollment', 'course.courseUnit'])->find($courseEnrollmentId) : null;

                if (! $courseEnrollment) {
                    $errors[] = ['index' => $index, 'action' => 'drop', 'message' => 'Inscription au cours introuvable.'];

                    continue;
                }

                $belongsToSelection =
                    $courseEnrollment->academic_year_id === $academicYear->id &&
                    (int) $courseEnrollment->semester === $semester &&
                    $courseEnrollment->enrollment?->academic_program_id === $courseEnrollment->course?->courseUnit?->academic_program_id &&
                    $courseEnrollment->course?->courseUnit?->academic_program_id !== null;

                if (! $belongsToSelection || ! isset($validEnrollmentSet[$courseEnrollment->enrollment_id], $validCourseSet[$courseEnrollment->course_id])) {
                    $errors[] = ['index' => $index, 'action' => 'drop', 'message' => 'Inscription au cours hors périmètre.'];

                    continue;
                }

                $gradeCount = Grade::query()->where('course_enrollment_id', $courseEnrollment->id)->count();
                $lockReason = $this->courseEnrollmentLockReason($courseEnrollment, $academicYear, false, $gradeCount);
                if ($lockReason) {
                    $errors[] = ['index' => $index, 'action' => 'drop', 'message' => $lockReason];

                    continue;
                }

                if ($courseEnrollment->status !== CourseEnrollment::STATUS_ENROLLED) {
                    $skipped++;

                    continue;
                }

                $courseEnrollment->update([
                    'status' => CourseEnrollment::STATUS_DROPPED,
                    'drop_date' => now()->toDateString(),
                ]);
                $dropped++;
            }
        });

        return [
            'created' => $created,
            'reactivated' => $reactivated,
            'dropped' => $dropped,
            'skipped' => $skipped,
            'errors' => $errors,
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

    private function isAcademicYearLocked(AcademicYear $academicYear): bool
    {
        return ! $academicYear->is_current
            || $academicYear->status === AcademicYearStatus::CLOSED->value
            || $academicYear->is_active === false;
    }

    private function isSemesterDeliberationLocked(string $programId, string $academicYearId, int $semester): bool
    {
        return DeliberationSession::query()
            ->where('academic_program_id', $programId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->whereIn('status', [
                DeliberationSession::STATUS_COMPLETED,
                DeliberationSession::STATUS_CLOSED,
            ])
            ->exists();
    }

    private function courseEnrollmentLockReason(
        ?CourseEnrollment $courseEnrollment,
        AcademicYear $academicYear,
        bool $deliberationLocked,
        int $gradeCount
    ): ?string {
        if ($this->isAcademicYearLocked($academicYear)) {
            return 'Année académique non modifiable.';
        }

        if ($deliberationLocked) {
            return 'Délibération clôturée pour ce semestre.';
        }

        if (! $courseEnrollment) {
            return null;
        }

        if ($courseEnrollment->status === CourseEnrollment::STATUS_COMPLETED) {
            return 'Cours terminé.';
        }

        if ($gradeCount > 0) {
            return 'Note déjà saisie.';
        }

        return null;
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
        if (! empty($missingPrerequisites)) {
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

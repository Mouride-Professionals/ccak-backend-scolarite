<?php

namespace App\Http\Controllers\Academic;

use App\Enums\RegistrationStatus;
use App\Enums\StudentStatus;
use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Enrollment\StoreEnrollmentRequest;
use App\Http\Requests\Enrollment\UpdateEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\Academic\CourseAutoEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EnrollmentController extends BaseApiController
{
    public function __construct(private CourseAutoEnrollmentService $autoEnroll)
    {
        $this->middleware('permission:enrollments.view')->only(['index', 'show', 'getByStudent']);
        $this->middleware('permission:enrollments.create')->only('store');
        $this->middleware('permission:enrollments.update')->only('update');
        $this->middleware('permission:enrollments.delete')->only('destroy');
        $this->middleware('permission:enrollments.generate_exam_numbers')->only('generateExamNumbers');
        $this->middleware('permission:enrollments.view_exam_number')->only('showExamNumber');
    }

    public function index(Request $request): JsonResponse
    {
        $enrollments = QueryBuilder::for(Enrollment::query())
            ->with(['student', 'academicProgram', 'academicYear'])
            ->allowedIncludes(['student', 'academicProgram', 'academicYear'])
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('academic_year_id'),
                AllowedFilter::exact('student_id'),
                AllowedFilter::exact('academic_program_id'),
                AllowedFilter::scope('enrollment_date_between'),
            ])
            ->allowedSorts(['created_at', 'enrollment_date', 'status'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success(EnrollmentResource::collection($enrollments), 'Enrollments retrieved successfully');
    }

    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $validator = Validator::make($request->all(), Enrollment::validationRules());

        if ($validator->fails()) {
            return $this->error('Erreur de validation.', 422, $validator->errors()->toArray());
        }

        // Check if student exists and is active
        $student = Student::find($request->student_id);
        if (! $student || $student->status !== StudentStatus::ACTIVE) {
            return $this->error('Étudiant non trouvé ou inactif.', 404);
        }

        // Check if academic program exists and is active
        $program = AcademicProgram::find($request->academic_program_id);
        if (! $program || ! $program->is_active) {
            return $this->error('Programme académique non trouvé ou inactif.', 404);
        }

        // Check if academic year exists and is active
        $year = AcademicYear::find($request->academic_year_id);
        if (! $year || ! $year->is_active) {
            return $this->error('Année académique non trouvée ou inactive.', 404);
        }

        // Check for duplicates
        if (Enrollment::isDuplicate($request->student_id, $request->academic_program_id, $request->academic_year_id)) {
            return $this->error('L\'étudiant est déjà inscrit à ce programme pour cette année académique.', 422);
        }

        try {
            $enrollment = DB::transaction(function () use ($request) {
                $enrollmentData = $request->all();
                $enrollmentData['current_semester'] = $enrollmentData['current_semester'] ?? 1;
                $enrollmentData['enrollment_date'] = $enrollmentData['enrollment_date'] ?? now();
                $enrollmentData['status'] = $enrollmentData['status'] ?? RegistrationStatus::PENDING_VALIDATION->value;

                return Enrollment::create($enrollmentData);
            });

            $autoResult = $this->autoEnroll->enrollAllCourses($enrollment);

            $resource = new EnrollmentResource($enrollment->load(['student', 'academicProgram', 'academicYear']));
            $resource->additional(['meta' => ['auto_enrolled_courses' => $autoResult['enrolled']]]);

            return $this->success(
                $resource,
                "Inscription créée avec succès. {$autoResult['enrolled']} cours inscrits automatiquement.",
                201
            );
        } catch (\Exception $e) {
            return $this->error('Erreur lors de la création de l\'inscription.', 500);
        }
    }

    public function show(int|string $id): JsonResponse
    {
        $enrollment = Enrollment::with([
            'student',
            'academicProgram',
            'academicYear',
            'courseEnrollments.course',
        ])->find($id);

        if (! $enrollment) {
            return $this->error('Inscription non trouvée.', 404);
        }

        return $this->success(new EnrollmentResource($enrollment));
    }

    public function update(UpdateEnrollmentRequest $request, int|string $id): JsonResponse
    {
        $enrollment = Enrollment::find($id);
        if (! $enrollment) {
            return $this->error('Inscription non trouvée.', 404);
        }
        $validator = Validator::make($request->all(), Enrollment::validationRules($id));

        if ($validator->fails()) {
            return $this->error('Erreur de validation.', 422, $validator->errors()->toArray());
        }

        // Check for duplicates (excluding current enrollment)
        if (Enrollment::isDuplicate(
            $request->student_id,
            $request->academic_program_id,
            $request->academic_year_id,
            $id
        )) {
            return $this->error('L\'étudiant est déjà inscrit à ce programme pour cette année académique.', 422);
        }

        DB::transaction(function () use ($enrollment, $request) {
            $enrollment->update($request->all());
        });

        return $this->success(
            new EnrollmentResource($enrollment->fresh(['student', 'academicProgram', 'academicYear'])),
            'Inscription mise à jour avec succès.'
        );
    }

    public function destroy(int|string $id): JsonResponse
    {
        $enrollment = Enrollment::find($id);

        if (! $enrollment) {
            return $this->error('Inscription non trouvée.', 404);
        }

        DB::transaction(fn () => $enrollment->delete());

        return $this->success(null, 'Inscription supprimée avec succès.');
    }

    /**
     * Bulk-generate exam numbers for all enrollments in an academic year
     * that do not yet have one. Uses lockForUpdate to prevent race conditions.
     */
    public function generateExamNumbers(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
        ]);

        $yearId = $request->input('academic_year_id');

        $year = \App\Models\AcademicYear::findOrFail($yearId);

        // Derive 4-char year code from name, e.g. "2024-2025" → "2425"
        preg_match_all('/\d{4}/', $year->name, $matches);
        $yearCode = ! empty($matches[0])
            ? implode('', array_map(fn ($y) => substr($y, 2), $matches[0]))
            : substr(preg_replace('/\D/', '', $year->name), 0, 4);

        $generated = 0;

        DB::transaction(function () use ($yearId, $yearCode, &$generated) {
            $enrollments = Enrollment::where('academic_year_id', $yearId)
                ->whereNull('exam_number')
                ->lockForUpdate()
                ->orderBy('created_at')
                ->get();

            // Find the current max sequence for this year
            $maxSeq = Enrollment::where('academic_year_id', $yearId)
                ->whereNotNull('exam_number')
                ->count();

            foreach ($enrollments as $enrollment) {
                $maxSeq++;
                $enrollment->exam_number = Enrollment::generateExamNumber($yearCode, $maxSeq);
                $enrollment->save();
                $generated++;
            }
        });

        return $this->success(['generated' => $generated], "{$generated} numéro(s) anonymat généré(s).");
    }

    /**
     * Return only the exam_number for a single enrollment.
     */
    public function showExamNumber(Enrollment $enrollment): JsonResponse
    {
        return $this->success([
            'enrollment_id' => $enrollment->id,
            'exam_number'   => $enrollment->exam_number,
        ]);
    }

    /**
     * Get all enrollments for a student
     */
    public function getByStudent(Request $request, string $studentId): JsonResponse
    {
        $student = Student::find($studentId);

        if (! $student) {
            return $this->error('Étudiant non trouvé.', 404);
        }

        $enrollments = QueryBuilder::for($student->enrollments()->getQuery())
            ->with([
                'academicProgram:id,name,level',
                'academicYear:id,name,start_date,end_date',
                'courseEnrollments.course:id,code,name,credits',
            ])
            ->allowedFilters([
                AllowedFilter::exact('academic_year_id'),
                AllowedFilter::exact('status'),
            ])
            ->allowedSorts(['enrollment_date', 'created_at'])
            ->defaultSort('-enrollment_date')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        // Format the response
        return $this->success(
            EnrollmentResource::collection($enrollments),
            'Inscriptions de l\'étudiant récupérées avec succès'
        );
    }
}

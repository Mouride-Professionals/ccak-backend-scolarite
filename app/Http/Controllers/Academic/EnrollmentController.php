<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Enrollment\StoreEnrollmentRequest;
use App\Http\Requests\Enrollment\UpdateEnrollmentRequest;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EnrollmentController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:enrollments.view')->only(['index', 'show', 'getByStudent']);
        $this->middleware('permission:enrollments.create')->only('store');
        $this->middleware('permission:enrollments.update')->only('update');
        $this->middleware('permission:enrollments.delete')->only('destroy');
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
            ])
            ->allowedSorts(['created_at', 'enrollment_date', 'status'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success($enrollments, 'Enrollments retrieved successfully');
    }

    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $validator = Validator::make($request->all(), Enrollment::validationRules());

        if ($validator->fails()) {
            return $this->error('Erreur de validation.', 422, $validator->errors()->toArray());
        }

        // Check if student exists and is active
        $student = Student::find($request->student_id);
        if (!$student || !$student->is_active) {
            return $this->error('Étudiant non trouvé ou inactif.', 404);
        }

        // Check if academic program exists and is active
        $program = AcademicProgram::find($request->academic_program_id);
        if (!$program || !$program->is_active) {
            return $this->error('Programme académique non trouvé ou inactif.', 404);
        }

        // Check if academic year exists and is active
        $year = AcademicYear::find($request->academic_year_id);
        if (!$year || !$year->is_active) {
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
                $enrollmentData['status'] = $enrollmentData['status'] ?? Enrollment::STATUS_PENDING;

                return Enrollment::create($enrollmentData);
            });

            return $this->success(
                $enrollment->load(['student', 'academicProgram', 'academicYear']),
                'Inscription créée avec succès.',
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
            'courseEnrollments.course'
        ])->find($id);

        if (!$enrollment) {
            return $this->error('Inscription non trouvée.', 404);
        }

        return $this->success($enrollment);
    }

    public function update(UpdateEnrollmentRequest $request, int|string $id): JsonResponse
    {
        $enrollment = Enrollment::find($id);
        if (!$enrollment) {
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
            $enrollment->fresh(['student', 'academicProgram', 'academicYear']),
            'Inscription mise à jour avec succès.'
        );
    }

    public function destroy(int|string $id): JsonResponse
    {
        $enrollment = Enrollment::find($id);

        if (!$enrollment) {
            return $this->error('Inscription non trouvée.', 404);
        }

        DB::transaction(fn() => $enrollment->delete());

        return $this->success(null, 'Inscription supprimée avec succès.');
    }

    /**
     * Get all enrollments for a student
    */
    public function getByStudent(Request $request, string $studentId): JsonResponse
    {
        $student = Student::find($studentId);

        if (!$student) {
            return $this->error('Étudiant non trouvé.', 404);
        }

        $enrollments = QueryBuilder::for($student->enrollments()->getQuery())
            ->with([
                'academicProgram:id,name,level',
                'academicYear:id,name,start_date,end_date',
                'courseEnrollments.course:id,code,name,credits'
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
        $formattedEnrollments = collect($enrollments->items())->map(function ($enrollment) {
            return [
                'id' => $enrollment->id,
                'student' => [
                    'id' => $enrollment->student->id,
                    'student_number' => $enrollment->student->student_number,
                    'full_name' => $enrollment->student->full_name,
                ],
                'program' => [
                    'id' => $enrollment->academicProgram->id,
                    'name' => $enrollment->academicProgram->name,
                    'level' => $enrollment->academicProgram->level,
                ],
                'academic_year' => [
                    'id' => $enrollment->academicYear->id,
                    'name' => $enrollment->academicYear->name,
                ],
                'current_semester' => $enrollment->current_semester,
                'status' => $enrollment->status,
                'enrollment_date' => $enrollment->enrollment_date->format('Y-m-d'),
                'registration_fee_paid' => $enrollment->registration_fee_paid,
                'is_scholarship' => $enrollment->is_scholarship,
                'courses' => $enrollment->courseEnrollments->map(function ($ce) {
                    return [
                        'id' => $ce->id,
                        'course_id' => $ce->course->id,
                        'code' => $ce->course->code,
                        'name' => $ce->course->name,
                        'credits' => $ce->course->credits,
                        'semester' => $ce->semester,
                        'status' => $ce->status,
                        'enrollment_date' => $ce->enrollment_date->format('Y-m-d'),
                        'drop_date' => $ce->drop_date?->format('Y-m-d'),
                    ];
                }),
                'total_courses' => $enrollment->courseEnrollments->count(),
                'active_courses' => $enrollment->courseEnrollments->where('status', 'ENROLLED')->count(),
                'completed_courses' => $enrollment->courseEnrollments->where('status', 'COMPLETED')->count(),
            ];
        });

        return $this->success([
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'full_name' => $student->full_name,
                'email' => $student->email,
            ],
            'enrollments' => $formattedEnrollments,
            'total_enrollments' => $enrollments->total(),
            'pagination' => [
                'current_page' => $enrollments->currentPage(),
                'last_page' => $enrollments->lastPage(),
                'per_page' => $enrollments->perPage(),
            ],
        ]);
    }
}

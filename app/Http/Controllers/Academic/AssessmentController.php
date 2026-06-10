<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreAssessmentRequest;
use App\Http\Requests\Academic\UpdateAssessmentRequest;
use App\Http\Resources\Academic\AssessmentResource;
use App\Models\Assessment;
use App\Models\CourseEnrollment;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:assessments.view')->only(['index', 'show', 'byCourse', 'gradeSheet']);
        $this->middleware('permission:assessments.create')->only('store');
        $this->middleware('permission:assessments.update')->only('update');
        $this->middleware('permission:assessments.delete')->only('destroy');
        $this->middleware('permission:assessments.publish_grades')->only('publishGrades');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Assessment::with(['course', 'facultyMember', 'academicYear'])
            ->when($request->academic_year_id, fn ($q) => $q->forAcademicYear($request->academic_year_id))
            ->when($request->faculty_member_id, fn ($q) => $q->forFacultyMember($request->faculty_member_id))
            ->when($request->course_id, fn ($q) => $q->forCourse($request->course_id))
            ->orderBy('date', 'desc');

        $assessments = $request->boolean('paginate', false)
            ? $query->paginate($request->integer('per_page', 20))
            : $query->get();

        return $this->success(AssessmentResource::collection($assessments));
    }

    public function store(StoreAssessmentRequest $request): JsonResponse
    {
        $assessment = Assessment::create($request->validated());

        return $this->success(
            new AssessmentResource($assessment->load(['course', 'facultyMember', 'academicYear'])),
            'Contrôle continu créé avec succès.',
            201
        );
    }

    public function show(Assessment $assessment): JsonResponse
    {
        $assessment->load(['course', 'facultyMember', 'academicYear']);

        return $this->success(new AssessmentResource($assessment));
    }

    public function update(UpdateAssessmentRequest $request, Assessment $assessment): JsonResponse
    {
        $assessment->update($request->validated());

        return $this->success(
            new AssessmentResource($assessment->fresh(['course', 'facultyMember', 'academicYear'])),
            'Contrôle continu mis à jour.'
        );
    }

    public function destroy(Assessment $assessment): JsonResponse
    {
        $assessment->delete();

        return $this->success(null, 'Contrôle continu supprimé.');
    }

    public function byCourse(Request $request, string $course): JsonResponse
    {
        $request->merge(['course_id' => $course]);

        return $this->index($request);
    }

    public function publishGrades(Assessment $assessment): JsonResponse
    {
        if ($assessment->is_grades_published) {
            return $this->errorResponse('Les notes sont déjà publiées.', 422);
        }

        $assessment->update(['is_grades_published' => true]);

        return $this->success(
            new AssessmentResource($assessment->fresh(['course', 'facultyMember', 'academicYear'])),
            'Notes publiées avec succès.'
        );
    }

    public function gradeSheet(Assessment $assessment): JsonResponse
    {
        $enrollments = CourseEnrollment::with('student')
            ->where('course_id', $assessment->course_id)
            ->where('academic_year_id', $assessment->academic_year_id)
            ->get();

        $grades = Grade::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy('student_id');

        $sheet = $enrollments->map(function ($enrollment) use ($grades) {
            $grade = $grades->get($enrollment->student_id);

            return [
                'student_id'         => $enrollment->student_id,
                'student_number'     => $enrollment->student?->student_number,
                'full_name'          => $enrollment->student?->full_name,
                'grade_id'           => $grade?->id,
                'score'              => $grade?->score,
                'max_score'          => $grade?->max_score ?? 20,
                'status'             => $grade?->status?->value,
                'course_enrollment_id' => $enrollment->id,
            ];
        })->sortBy('full_name')->values();

        return $this->success([
            'assessment' => new AssessmentResource($assessment->load(['course', 'facultyMember', 'academicYear'])),
            'students'   => $sheet,
        ]);
    }
}

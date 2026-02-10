<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Models\User;
use App\Services\Student\StudentNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Enums\GradeStatus;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class StudentController extends BaseApiController
{
    public function __construct(
        private StudentNumberService $studentNumberService
    ) {
        $this->middleware('permission:students.view')->only(['index', 'show']);
        $this->middleware('permission:students.create')->only('store');
        $this->middleware('permission:students.update')->only('update');
        $this->middleware('permission:students.delete')->only('destroy');
        $this->middleware('permission:grades.view')->only('grades');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $students = QueryBuilder::for(Student::query())
            ->with('user')
            ->allowedIncludes(['user'])
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('gender'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['id', 'student_number', 'full_name', 'status', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->get('per_page', 15));

        return $this->success(
            StudentResource::collection($students),
            'Liste des étudiants récupérée avec succès.'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        try {
            $user = User::findOrFail($request->user_id);

            Log::info('User found', ['user_id' => $request->user_id, 'user' => $user]);

            if ($user->student) {
                return $this->error('Un profil étudiant existe déjà pour cet utilisateur.', 409);
            }

            $student = DB::transaction(function () use ($request) {
                // Générer le numéro d'étudiant
                $studentNumber = $this->studentNumberService->generate();

                // Créer le profil étudiant
                $student = Student::create([
                    'user_id' => $request->user_id,
                    'student_number' => $studentNumber,
                    'full_name' => $request->full_name,
                    'gender' => $request->gender,
                    'date_of_birth' => $request->date_of_birth,
                    'place_of_birth' => $request->place_of_birth,
                    'nationality' => $request->nationality,
                    'phone' => $request->phone,
                    'emergency_contact_name' => $request->emergency_contact_name,
                    'emergency_contact_phone' => $request->emergency_contact_phone,
                    'address' => $request->address,
                    'photo_url' => $request->photo_url,
                    'status' => $request->status ?? 'ACTIVE',
                ]);

                Log::info('Student created', ['id' => $student->id, 'student_number' => $student->student_number]);

                return $student;
            });

            return $this->success(
                new StudentResource($student->load('user')),
                'Profil étudiant créé avec succès.',
                201
            );
        } catch (\Exception $e) {
            return $this->error('Erreur lors de la création du profil étudiant.', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student): JsonResponse
    {
        $this->authorize('view', $student);

        $student->load(['user', 'guardians', 'documents']);

        return $this->success(
            new StudentResource($student),
            'Profil étudiant récupéré avec succès.'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $this->authorize('update', $student);

        try {
            $student = DB::transaction(function () use ($request, $student) {
                $student->update($request->validated());
                return $student;
            });

            return $this->success(
                new StudentResource($student->load('user')),
                'Profil étudiant mis à jour avec succès.'
            );
        } catch (\Exception $e) {
            return $this->error('Erreur lors de la mise à jour du profil étudiant.', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Get grades for a student
     */
    public function grades(Request $request, Student $student): JsonResponse
    {
        $this->authorize('viewGrades', $student);

        $query = QueryBuilder::for($student->grades()->getQuery())
            ->with(['course', 'courseEnrollment'])
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('semester', function ($query, $value) {
                    $query->whereHas('courseEnrollment', fn($sub) => $sub->where('semester', $value));
                }),
                AllowedFilter::callback('academic_year_id', function ($query, $value) {
                    $query->whereHas('courseEnrollment', fn($sub) => $sub->where('academic_year_id', $value));
                }),
            ])
            ->allowedSorts(['score', 'type', 'status', 'created_at'])
            ->defaultSort('-created_at');

        $user = $request->user();
        if ($user && $this->isStudent($user)) {
            $query->where('status', GradeStatus::PUBLISHED);
        }

        $grades = $query->get();

        $groupedGrades = $grades->groupBy('course_id')->map(function ($courseGrades) {
            $course = $courseGrades->first()->course;
            $gradesByType = $courseGrades->groupBy('type');

            $averageScore = $courseGrades->avg('score');
            $maxPossibleScore = $courseGrades->avg('max_score');
            $weightedAverage = $courseGrades->sum(function ($grade) {
                return ($grade->score / $grade->max_score) * $grade->weight;
            }) / max(1, $courseGrades->sum('weight'));

            return [
                'course' => [
                    'id' => $course->id,
                    'code' => $course->code,
                    'name' => $course->name,
                    'credits' => $course->credits,
                ],
                'grades' => $gradesByType->map(function ($grades, $type) {
                    return [
                        'type' => $type,
                        'grades' => $grades->map(function ($grade) {
                            return [
                                'id' => $grade->id,
                                'score' => $grade->score,
                                'max_score' => $grade->max_score,
                                'weight' => $grade->weight,
                                'status' => $grade->status,
                                'entered_at' => $grade->entered_at,
                                'validated_at' => $grade->validated_at,
                            ];
                        })->values(),
                        'average' => $grades->avg('score'),
                        'max_average' => $grades->avg('max_score'),
                    ];
                }),
                'averages' => [
                    'simple_average' => round($averageScore, 2),
                    'max_possible' => round($maxPossibleScore, 2),
                    'weighted_average' => round($weightedAverage * 100, 2),
                    'percentage' => $maxPossibleScore > 0 ? round(($averageScore / $maxPossibleScore) * 100, 2) : 0,
                ],
                'total_grades' => $courseGrades->count(),
            ];
        })->values();

        return $this->success([
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'full_name' => $student->full_name,
            ],
            'courses' => $groupedGrades,
            'summary' => [
                'total_courses' => $groupedGrades->count(),
                'total_grades' => $grades->count(),
                'overall_average' => round($grades->avg('score'), 2),
            ],
        ], 'Student grades retrieved successfully');
    }

    private function isStudent($user): bool
    {
        return $user->hasRole('STUDENT');
    }
}

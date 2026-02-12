<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\ShareEvaluationRequest;
use App\Http\Requests\Academic\StoreEvaluationRequest;
use App\Http\Resources\Academic\EvaluationResource;
use App\Models\CourseEnrollment;
use App\Models\Evaluation;
use App\Models\EvaluationResponse;
use App\Models\FacultyMember;
use App\Models\Student;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EvaluationController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:evaluations.create')->only('store');
        $this->middleware('permission:evaluations.update')->only('share');
        $this->middleware('permission:evaluations.view')->only(['index', 'results', 'studentEvaluations']);
    }

    public function index(Request $request): JsonResponse
    {
        $evaluations = QueryBuilder::for(Evaluation::query())
            ->with(['course', 'facultyMember', 'academicYear'])
            ->allowedIncludes(['course', 'facultyMember', 'academicYear'])
            ->allowedFilters([
                AllowedFilter::exact('course_id'),
                AllowedFilter::exact('faculty_member_id'),
                AllowedFilter::exact('academic_year_id'),
                AllowedFilter::exact('is_published'),
            ])
            ->allowedSorts(['created_at', 'start_date', 'response_deadline'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page') ?: 15)
            ->appends($request->query());

        return $this->success(
            EvaluationResource::collection($evaluations),
            'Evaluations retrieved successfully'
        );
    }

    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $evaluation = DB::transaction(fn() => Evaluation::create($data));

        return $this->success(new EvaluationResource($evaluation), 'Evaluation created', 201);
    }

    public function share(ShareEvaluationRequest $request, Evaluation $evaluation): JsonResponse
    {
        $publish = $request->boolean('publish', true);

        DB::transaction(function () use ($evaluation, $publish) {
            $evaluation->update(['is_published' => $publish]);
        });

        if ($publish) {
            $this->notifyEnrolledStudents($evaluation);
        }

        return $this->success(new EvaluationResource($evaluation->refresh()), 'Evaluation shared');
    }

    public function results(Evaluation $evaluation, Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$this->canViewResults($user, $evaluation)) {
            return $this->error('Forbidden', 403);
        }

        $responses = $evaluation->responses()->get();
        $scores = $responses->flatMap(function ($response) {
            return array_filter((array) $response->rating_scores, 'is_numeric');
        })->values();

        $average = $scores->isEmpty() ? null : round($scores->avg(), 2);

        return $this->success([
            'evaluation' => new EvaluationResource($evaluation),
            'response_count' => $responses->count(),
            'average_score' => $average,
            'responses' => $responses,
        ]);
    }

    public function studentEvaluations(Student $student): JsonResponse
    {
        $evaluationIds = EvaluationResponse::where('student_id', $student->id)->pluck('evaluation_id')->toArray();

        $pending = Evaluation::query()
            ->published()
            ->whereNotIn('id', $evaluationIds)
            ->get();

        $completed = Evaluation::query()
            ->whereIn('id', $evaluationIds)
            ->get();

        return $this->success([
            'student_id' => $student->id,
            'pending' => EvaluationResource::collection($pending),
            'completed' => EvaluationResource::collection($completed),
        ]);
    }

    private function notifyEnrolledStudents(Evaluation $evaluation): void
    {
        $query = CourseEnrollment::query()
            ->where('course_id', $evaluation->course_id)
            ->where('status', CourseEnrollment::STATUS_ENROLLED);

        if ($evaluation->academic_year_id) {
            $query->where('academic_year_id', $evaluation->academic_year_id);
        }

        $studentIds = $query->pluck('student_id')->unique();
        $userIds = Student::whereIn('id', $studentIds)->pluck('user_id')->filter();

        $notificationService = app(NotificationService::class);
        foreach ($userIds as $userId) {
            $notificationService->send(
                $userId,
                'Nouvelle évaluation',
                'Une nouvelle évaluation est disponible pour votre cours.',
                \App\Models\Notification::TYPE_SYSTEM,
                ['in_app'],
                ['evaluation_id' => $evaluation->id]
            );
        }
    }

    private function canViewResults(User $user, Evaluation $evaluation): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }

        $faculty = FacultyMember::where('user_id', $user->id)->first();
        return $faculty && $faculty->id === $evaluation->faculty_member_id;
    }
}

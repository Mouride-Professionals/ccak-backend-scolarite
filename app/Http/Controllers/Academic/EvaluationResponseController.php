<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreEvaluationResponseRequest;
use App\Http\Resources\Academic\EvaluationResponseResource;
use App\Models\CourseEnrollment;
use App\Models\Evaluation;
use App\Models\EvaluationResponse;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EvaluationResponseController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:evaluation_responses.create')->only('store');
    }

    public function store(StoreEvaluationResponseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $evaluation = Evaluation::findOrFail($data['evaluation_id']);

        if (!$evaluation->is_published) {
            return $this->error('Evaluation is not published.', 422);
        }

        if ($evaluation->response_deadline && now()->gt($evaluation->response_deadline)) {
            return $this->error('Evaluation deadline has passed.', 422);
        }

        $student = Student::where('user_id', $request->user()->id)->first();
        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $enrolled = CourseEnrollment::query()
            ->where('course_id', $evaluation->course_id)
            ->where('student_id', $student->id)
            ->when($evaluation->academic_year_id, function ($query) use ($evaluation) {
                $query->where('academic_year_id', $evaluation->academic_year_id);
            })
            ->where('status', CourseEnrollment::STATUS_ENROLLED)
            ->exists();

        if (!$enrolled) {
            return $this->error('Student is not enrolled in this course.', 403);
        }

        $existing = EvaluationResponse::where('evaluation_id', $evaluation->id)
            ->where('student_id', $student->id)
            ->exists();

        if ($existing) {
            return $this->error('Response already submitted.', 422);
        }

        $response = DB::transaction(function () use ($data, $student, $evaluation) {
            return EvaluationResponse::create([
                'evaluation_id' => $evaluation->id,
                'student_id' => $student->id,
                'responses' => $data['responses'],
                'rating_scores' => $data['rating_scores'] ?? [],
                'comments' => $data['comments'] ?? null,
                'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
                'submitted_at' => now(),
            ]);
        });

        return $this->success(new EvaluationResponseResource($response), 'Evaluation submitted', 201);
    }
}

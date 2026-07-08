<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Models\CourseEnrollment;
use App\Models\ExamSchedule;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamGradeSheetController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:exam_grades.view')->only('gradeSheet');
        $this->middleware('permission:exam_grades.create')->only('storeGrade');
    }

    public function gradeSheet(ExamSchedule $examSchedule): JsonResponse
    {
        $examSchedule->load(['course', 'examSession', 'room']);
        $session = $examSchedule->examSession;
        $useAnonyma = (bool) $session->use_exam_number;

        $enrollments = CourseEnrollment::with(['student', 'enrollment'])
            ->where('course_id', $examSchedule->course_id)
            ->where('academic_year_id', $session->academic_year_id)
            ->get();

        $grades = Grade::where('exam_schedule_id', $examSchedule->id)
            ->get()
            ->keyBy('course_enrollment_id');

        $sheet = $enrollments->map(function ($ce) use ($grades, $useAnonyma) {
            $grade = $grades->get($ce->id);

            $row = [
                'student_id' => $ce->student_id,
                'course_enrollment_id' => $ce->id,
                'grade_id' => $grade?->id,
                'score' => $grade?->score,
                'max_score' => $grade?->max_score ?? 20,
                'status' => $grade?->status?->value,
            ];

            if ($useAnonyma) {
                $row['exam_number'] = $ce->enrollment?->exam_number;
            } else {
                $row['full_name'] = $ce->student?->full_name;
                $row['student_number'] = $ce->student?->student_number;
            }

            return $row;
        });

        $sheet = $useAnonyma
            ? $sheet->sortBy('exam_number')->values()
            : $sheet->sortBy('full_name')->values();

        return $this->success([
            'exam_schedule' => [
                'id' => $examSchedule->id,
                'course_id' => $examSchedule->course_id,
                'course_code' => $examSchedule->course?->code,
                'course_name' => $examSchedule->course?->name,
                'session_id' => $session->id,
                'session_name' => $session->name,
                'date' => $examSchedule->date?->toDateString(),
                'start_time' => $examSchedule->start_time,
                'end_time' => $examSchedule->end_time,
                'room' => $examSchedule->room?->name,
                'semester_number' => $session->semester_number,
                'use_exam_number' => $useAnonyma,
            ],
            'students' => $sheet,
        ]);
    }

    public function storeGrade(Request $request, ExamSchedule $examSchedule): JsonResponse
    {
        $validated = $request->validate([
            'course_enrollment_id' => 'required|uuid|exists:course_enrollments,id',
            'score' => 'required|numeric|min:0',
            'max_score' => 'sometimes|numeric|min:1',
            'weight' => 'sometimes|numeric|min:0',
        ]);

        $ce = CourseEnrollment::find($validated['course_enrollment_id']);

        $grade = Grade::updateOrCreate(
            [
                'course_enrollment_id' => $validated['course_enrollment_id'],
                'exam_schedule_id' => $examSchedule->id,
                'type' => 'EXAM',
            ],
            [
                'student_id' => $ce->student_id,
                'course_id' => $examSchedule->course_id,
                'score' => $validated['score'],
                'max_score' => $validated['max_score'] ?? 20,
                'weight' => $validated['weight'] ?? 1,
                'entered_by' => auth()->id(),
                'entered_at' => now(),
            ]
        );

        return $this->success($grade, 'Grade saved.', 201);
    }
}

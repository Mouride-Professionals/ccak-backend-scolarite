<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeStatisticsController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:grades.view')->only('index');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Grade::query();

        if ($request->filled('course_id')) {
            $query->where('course_id', (string) $request->input('course_id'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', (string) $request->input('student_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', (string) $request->input('type'));
        }

        if ($request->filled('academic_year_id')) {
            $query->whereHas('courseEnrollment', function ($sub) use ($request): void {
                $sub->where('academic_year_id', (string) $request->input('academic_year_id'));
            });
        }

        if ($request->filled('semester')) {
            $query->whereHas('courseEnrollment', function ($sub) use ($request): void {
                $sub->where('semester', (int) $request->input('semester'));
            });
        }

        $programId = $request->input('academic_program_id', $request->input('program_id'));
        if ($programId) {
            $query->whereHas('course.courseUnit', function ($sub) use ($programId): void {
                $sub->where('academic_program_id', (string) $programId);
            });
        }

        $grades = (clone $query)->get(['score', 'max_score']);
        $normalizedGrades = $grades
            ->map(function ($grade): ?float {
                if ((float) $grade->max_score <= 0) {
                    return null;
                }

                return round(((float) $grade->score / (float) $grade->max_score) * 20, 2);
            })
            ->filter(fn($value) => $value !== null)
            ->values();

        $count = $normalizedGrades->count();

        if ($count === 0) {
            return $this->success([
                'count' => 0,
                'average' => null,
                'median' => null,
                'min' => null,
                'max' => null,
                'pass_rate' => 0.0,
                'distribution' => [
                    'excellent' => 0,
                    'good' => 0,
                    'average' => 0,
                    'pass' => 0,
                    'fail' => 0,
                ],
                'filters' => $request->only([
                    'course_id',
                    'student_id',
                    'status',
                    'type',
                    'academic_year_id',
                    'semester',
                    'academic_program_id',
                    'program_id',
                ]),
            ], 'Aucune note trouvée pour les filtres fournis.');
        }

        $sorted = $normalizedGrades->sort()->values();
        $passCount = $normalizedGrades->filter(fn(float $grade) => $grade >= 10.0)->count();

        $statistics = [
            'count' => $count,
            'average' => round((float) $normalizedGrades->avg(), 2),
            'median' => $this->calculateMedian($sorted->all()),
            'min' => round((float) $normalizedGrades->min(), 2),
            'max' => round((float) $normalizedGrades->max(), 2),
            'pass_rate' => round(($passCount / $count) * 100, 2),
            'distribution' => [
                'excellent' => $normalizedGrades->filter(fn(float $grade) => $grade >= 16.0)->count(),
                'good' => $normalizedGrades->filter(fn(float $grade) => $grade >= 14.0 && $grade < 16.0)->count(),
                'average' => $normalizedGrades->filter(fn(float $grade) => $grade >= 12.0 && $grade < 14.0)->count(),
                'pass' => $normalizedGrades->filter(fn(float $grade) => $grade >= 10.0 && $grade < 12.0)->count(),
                'fail' => $normalizedGrades->filter(fn(float $grade) => $grade < 10.0)->count(),
            ],
            'filters' => $request->only([
                'course_id',
                'student_id',
                'status',
                'type',
                'academic_year_id',
                'semester',
                'academic_program_id',
                'program_id',
            ]),
        ];

        return $this->success($statistics, 'Grade statistics retrieved successfully');
    }

    /**
     * @param array<int, float|int|string> $values
     */
    private function calculateMedian(array $values): float
    {
        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        $middle = intdiv($count, 2);

        if ($count % 2 === 0) {
            return round((((float) $values[$middle - 1]) + ((float) $values[$middle])) / 2, 2);
        }

        return round((float) $values[$middle], 2);
    }
}

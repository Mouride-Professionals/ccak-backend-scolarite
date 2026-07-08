<?php

namespace App\Http\Controllers\Academic;

use App\Exports\GradeSheetExport;
use App\Http\Controllers\BaseApiController;
use App\Imports\GradeSheetImport;
use App\Models\Assessment;
use App\Models\CourseEnrollment;
use App\Models\ExamSchedule;
use App\Models\Grade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GradeSheetController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:grade_sheets.export')->only(['exportPdf', 'exportExcel']);
        $this->middleware('permission:grade_sheets.import')->only('importGrades');
    }

    // ── Context resolver ─────────────────────────────────────────────────

    private function resolveContext(string $type, string $id): array
    {
        if ($type === 'assessment') {
            $assessment = Assessment::with(['course', 'facultyMember', 'academicYear'])->findOrFail($id);

            $enrollments = CourseEnrollment::with(['student', 'enrollment'])
                ->where('course_id', $assessment->course_id)
                ->where('academic_year_id', $assessment->academic_year_id)
                ->get();

            $grades = Grade::where('assessment_id', $assessment->id)
                ->get()->keyBy('course_enrollment_id');

            return [
                'type' => 'assessment',
                'course_code' => $assessment->course?->code,
                'course_name' => $assessment->course?->name,
                'session_label' => $assessment->title,
                'date' => $assessment->date?->toDateString(),
                'academic_year_name' => $assessment->academicYear?->name,
                'semester_number' => null,
                'use_exam_number' => false,
                'enrollments' => $enrollments,
                'grades' => $grades,
                'grade_type' => 'CC',
                'match_key' => 'assessment_id',
                'match_value' => $assessment->id,
            ];
        }

        // exam_schedule
        $schedule = ExamSchedule::with(['course', 'examSession.academicYear', 'room'])->findOrFail($id);
        $session = $schedule->examSession;

        $enrollments = CourseEnrollment::with(['student', 'enrollment'])
            ->where('course_id', $schedule->course_id)
            ->where('academic_year_id', $session->academic_year_id)
            ->get();

        $grades = Grade::where('exam_schedule_id', $schedule->id)
            ->get()->keyBy('course_enrollment_id');

        return [
            'type' => 'exam_schedule',
            'course_code' => $schedule->course?->code,
            'course_name' => $schedule->course?->name,
            'session_label' => $session->name.' — '.($schedule->date?->format('d/m/Y') ?? ''),
            'date' => $schedule->date?->toDateString(),
            'academic_year_name' => $session->academicYear?->name,
            'semester_number' => $session->semester_number,
            'use_exam_number' => (bool) $session->use_exam_number,
            'enrollments' => $enrollments,
            'grades' => $grades,
            'grade_type' => 'EXAM',
            'match_key' => 'exam_schedule_id',
            'match_value' => $schedule->id,
        ];
    }

    private function buildStudentRows(array $ctx): \Illuminate\Support\Collection
    {
        $useAnonyma = $ctx['use_exam_number'];

        $rows = $ctx['enrollments']->map(function ($ce) use ($ctx, $useAnonyma) {
            $grade = $ctx['grades']->get($ce->id);

            $row = [
                'course_enrollment_id' => $ce->id,
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

        return $useAnonyma
            ? $rows->sortBy('exam_number')->values()
            : $rows->sortBy('full_name')->values();
    }

    // ── PDF export ───────────────────────────────────────────────────────

    public function exportPdf(string $type, string $id): Response
    {
        abort_unless(in_array($type, ['assessment', 'exam_schedule'], true), 404);

        $ctx = $this->resolveContext($type, $id);
        $students = $this->buildStudentRows($ctx)->toArray();

        $pdf = Pdf::loadView('pdf.grade-sheet', compact('ctx', 'students'));
        $filename = 'fiche-de-note-'.Str::slug($ctx['course_code'] ?? $id).'.pdf';

        return $pdf->download($filename);
    }

    // ── Excel export ─────────────────────────────────────────────────────

    public function exportExcel(string $type, string $id): BinaryFileResponse
    {
        abort_unless(in_array($type, ['assessment', 'exam_schedule'], true), 404);

        $ctx = $this->resolveContext($type, $id);
        $students = $this->buildStudentRows($ctx)->toArray();

        $headerMeta = [
            'course_code' => $ctx['course_code'],
            'course_name' => $ctx['course_name'],
            'session_label' => $ctx['session_label'],
            'academic_year_name' => $ctx['academic_year_name'],
            'semester_number' => $ctx['semester_number'],
            'date' => $ctx['date'],
        ];

        $filename = 'fiche-de-note-'.Str::slug($ctx['course_code'] ?? $id).'.xlsx';

        return Excel::download(
            new GradeSheetExport($headerMeta, $students, $ctx['use_exam_number']),
            $filename
        );
    }

    // ── Excel import ─────────────────────────────────────────────────────

    public function importGrades(Request $request, string $type, string $id): JsonResponse
    {
        abort_unless(in_array($type, ['assessment', 'exam_schedule'], true), 404);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);

        $ctx = $this->resolveContext($type, $id);

        $importer = new GradeSheetImport;
        $sheets = Excel::toArray($importer, $request->file('file'));
        $rows = $sheets[0] ?? [];

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $row) {
            $enrollmentId = trim((string) ($row[0] ?? ''));

            // Skip rows whose col A is not a UUID (header/block rows)
            if (! Str::isUuid($enrollmentId)) {
                continue;
            }

            $scoreRaw = $row[4] ?? null; // col E = index 4
            if ($scoreRaw === null || $scoreRaw === '') {
                $skipped++;

                continue;
            }

            if (! is_numeric($scoreRaw)) {
                $errors[] = 'Ligne '.($rowIndex + 1).' : valeur invalide pour la note ('.$scoreRaw.')';
                $skipped++;

                continue;
            }

            $ce = CourseEnrollment::find($enrollmentId);
            if (! $ce) {
                $skipped++;

                continue;
            }

            $conditions = [
                'course_enrollment_id' => $enrollmentId,
                'type' => $ctx['grade_type'],
                $ctx['match_key'] => $ctx['match_value'],
            ];

            Grade::updateOrCreate($conditions, [
                'student_id' => $ce->student_id,
                'course_id' => $ce->course_id,
                'score' => (float) $scoreRaw,
                'max_score' => is_numeric($row[5] ?? null) ? (float) $row[5] : 20,
                'weight' => 1,
                'entered_by' => auth()->id(),
                'entered_at' => now(),
            ]);

            $imported++;
        }

        return $this->success(
            ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors],
            "{$imported} note(s) importée(s)."
        );
    }
}

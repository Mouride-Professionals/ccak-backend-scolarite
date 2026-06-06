<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Academic\StoreAttendanceRequest;
use App\Http\Resources\Academic\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLog;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:attendance.create')->only('store');
        $this->middleware('permission:attendance.view')->only(['studentAttendance', 'courseAttendance', 'dispensations']);
    }

    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $courseLog = CourseLog::with('schedule')->findOrFail($data['course_log_id']);
        $schedule = $courseLog->schedule;

        $studentIds = collect($data['records'])->pluck('student_id')->unique()->values();
        $enrolledIds = CourseEnrollment::query()
            ->where('course_id', $schedule->course_id)
            ->where('academic_year_id', $schedule->academic_year_id)
            ->where('status', CourseEnrollment::STATUS_ENROLLED)
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->all();

        $invalid = $studentIds->diff($enrolledIds)->values()->all();
        if (! empty($invalid)) {
            return $this->error('Some students are not enrolled in this course.', 422, ['student_ids' => $invalid]);
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($data, $courseLog, $schedule, &$created, &$updated) {
            foreach ($data['records'] as $record) {
                $payload = [
                    'course_log_id' => $courseLog->id,
                    'student_id' => $record['student_id'],
                    'status' => $record['status'],
                    'marked_at' => Carbon::now(),
                    'notes' => $record['notes'] ?? null,
                ];

                $attendance = AttendanceRecord::where('course_log_id', $courseLog->id)
                    ->where('student_id', $record['student_id'])
                    ->first();

                if ($attendance) {
                    $attendance->update($payload);
                    $updated++;
                } else {
                    $attendance = AttendanceRecord::create($payload);
                    $created++;
                }

                $absenceData = $this->calculateAbsenceStats($record['student_id'], $schedule->course_id);
                $attendance->update([
                    'absence_count' => $absenceData['absence_count'],
                    'is_dispensed' => $absenceData['is_dispensed'],
                ]);
            }
        });

        return $this->success([
            'created' => $created,
            'updated' => $updated,
        ], 'Attendance recorded');
    }

    public function studentAttendance(Request $request, Student $student): JsonResponse
    {
        $records = AttendanceRecord::query()
            ->with('courseLog.schedule.course')
            ->where('student_id', $student->id)
            ->when($request->filled('course_id'), function ($query) use ($request) {
                $query->whereHas('courseLog.schedule', function ($sub) use ($request) {
                    $sub->where('course_id', $request->input('course_id'));
                });
            })
            ->orderByDesc('marked_at')
            ->get();

        $summary = $records->groupBy(function ($record) {
            return $record->courseLog?->schedule?->course_id;
        })->map(function ($items) {
            $total = $items->count();
            $present = $items->where('status', AttendanceRecord::STATUS_PRESENT)->count();
            $absent = $items->where('status', AttendanceRecord::STATUS_ABSENT)->count();
            $late = $items->where('status', AttendanceRecord::STATUS_LATE)->count();
            $excused = $items->where('status', AttendanceRecord::STATUS_EXCUSED)->count();
            $rate = $total > 0 ? round(($present + $late + $excused) / $total * 100, 2) : 0;

            return [
                'total_sessions' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'excused' => $excused,
                'attendance_rate' => $rate,
                'absence_count' => $items->max('absence_count'),
                'is_dispensed' => (bool) $items->max('is_dispensed'),
            ];
        });

        return $this->success([
            'student_id' => $student->id,
            'summary' => $summary,
            'records' => AttendanceRecordResource::collection($records),
        ]);
    }

    public function courseAttendance(Request $request, Course $course): JsonResponse
    {
        $records = AttendanceRecord::query()
            ->with(['student', 'courseLog.schedule'])
            ->whereHas('courseLog.schedule', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->when($request->filled('session_date'), function ($query) use ($request) {
                $query->whereHas('courseLog', function ($sub) use ($request) {
                    $sub->whereDate('session_date', $request->input('session_date'));
                });
            })
            ->orderByDesc('marked_at')
            ->get();

        return $this->success(AttendanceRecordResource::collection($records));
    }

    public function dispensations(Student $student): JsonResponse
    {
        $records = AttendanceRecord::query()
            ->with('courseLog.schedule.course')
            ->where('student_id', $student->id)
            ->where('is_dispensed', true)
            ->get();

        $courses = $records->map(function ($record) {
            return $record->courseLog?->schedule?->course;
        })->filter()->unique('id')->values();

        return $this->success([
            'student_id' => $student->id,
            'courses' => $courses,
        ]);
    }

    /** @return array{absence_count:int, is_dispensed:bool} */
    private function calculateAbsenceStats(string $studentId, string $courseId): array
    {
        $absences = AttendanceRecord::query()
            ->where('student_id', $studentId)
            ->where('status', AttendanceRecord::STATUS_ABSENT)
            ->whereHas('courseLog.schedule', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->whereHas('courseLog.schedule.activityType', function ($query) {
                $query->whereIn('code', ['TD', 'TP']);
            })
            ->count();

        return [
            'absence_count' => $absences,
            'is_dispensed' => $absences >= 3,
        ];
    }
}

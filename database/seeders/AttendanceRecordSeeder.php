<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\CourseEnrollment;
use App\Models\CourseLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceRecordSeeder extends Seeder
{
    public function run(): void
    {
        $logs = CourseLog::with('schedule.activityType')->get();
        if ($logs->isEmpty()) {
            $this->call(CourseLogSeeder::class);
            $logs = CourseLog::with('schedule.activityType')->get();
        }

        foreach ($logs as $log) {
            $schedule = $log->schedule;
            if (! $schedule) {
                continue;
            }

            $students = CourseEnrollment::where('course_id', $schedule->course_id)
                ->where('academic_year_id', $schedule->academic_year_id)
                ->where('status', CourseEnrollment::STATUS_ENROLLED)
                ->pluck('student_id');

            foreach ($students as $studentId) {
                $status = collect([
                    AttendanceRecord::STATUS_PRESENT,
                    AttendanceRecord::STATUS_PRESENT,
                    AttendanceRecord::STATUS_PRESENT,
                    AttendanceRecord::STATUS_LATE,
                    AttendanceRecord::STATUS_ABSENT,
                    AttendanceRecord::STATUS_EXCUSED,
                ])->random();

                AttendanceRecord::firstOrCreate(
                    [
                        'course_log_id' => $log->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'status' => $status,
                        'marked_at' => Carbon::now(),
                        'notes' => $status === AttendanceRecord::STATUS_ABSENT ? 'Absence non justifiée.' : null,
                        'absence_count' => 0,
                        'is_dispensed' => false,
                    ]
                );
            }
        }

        $this->updateAbsenceCounts();
    }

    private function updateAbsenceCounts(): void
    {
        $records = AttendanceRecord::with('courseLog.schedule.activityType')->get();

        $grouped = $records->groupBy(function ($record) {
            $courseId = $record->courseLog?->schedule?->course_id;

            return $record->student_id.'|'.$courseId;
        });

        foreach ($grouped as $group) {
            $courseId = $group->first()->courseLog?->schedule?->course_id;
            if (! $courseId) {
                continue;
            }

            $absences = $group->filter(function ($record) {
                $activityCode = $record->courseLog?->schedule?->activityType?->code;

                return $record->status === AttendanceRecord::STATUS_ABSENT
                    && in_array($activityCode, ['TD', 'TP'], true);
            })->count();

            $dispensed = $absences >= 3;

            foreach ($group as $record) {
                $record->update([
                    'absence_count' => $absences,
                    'is_dispensed' => $dispensed,
                ]);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ActivityType;
use App\Models\Course;
use App\Models\FacultyMember;
use App\Models\Room;
use App\Models\Schedule;
use Database\Seeders\Concerns\UsesSenegalAcademicCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ScheduleSeeder extends Seeder
{
    use UsesSenegalAcademicCalendar;

    public function run(): void
    {
        $years = AcademicYear::all();
        if ($years->isEmpty()) {
            $this->call(AcademicYearSeeder::class);
            $years = AcademicYear::all();
        }

        $courses = Course::with('courseUnit')->get();
        if ($courses->isEmpty()) {
            $this->call(CourseSeeder::class);
            $courses = Course::with('courseUnit')->get();
        }

        $facultyMembers = FacultyMember::all();
        if ($facultyMembers->isEmpty()) {
            $this->call(FacultyMemberSeeder::class);
            $facultyMembers = FacultyMember::all();
        }

        $rooms = Room::all();
        if ($rooms->isEmpty()) {
            $this->call(RoomSeeder::class);
            $rooms = Room::all();
        }

        $activityTypes = ActivityType::all();
        if ($activityTypes->isEmpty()) {
            $this->call(ActivityTypeSeeder::class);
            $activityTypes = ActivityType::all();
        }

        $slots = [
            ['start' => '08:00', 'end' => '10:00'],
            ['start' => '10:15', 'end' => '12:15'],
            ['start' => '14:00', 'end' => '16:00'],
            ['start' => '16:15', 'end' => '18:15'],
        ];

        $used = [];

        foreach ($years as $year) {
            foreach ($courses->take(12) as $course) {
                $semester = (int) ($course->courseUnit?->semester_number ?? 1);
                [$start, $end] = $this->semesterWindow($year->name, $semester);
                $day = random_int(1, 6);
                $slot = $slots[array_rand($slots)];

                $key = $year->id . '|' . $day . '|' . $slot['start'] . '|' . $slot['end'];
                $attempts = 0;
                while (isset($used[$key]) && $attempts < 10) {
                    $day = random_int(1, 6);
                    $slot = $slots[array_rand($slots)];
                    $key = $year->id . '|' . $day . '|' . $slot['start'] . '|' . $slot['end'];
                    $attempts++;
                }
                $used[$key] = true;

                $faculty = $facultyMembers->random();
                $room = $rooms->random();
                $activity = $activityTypes->random();

                Schedule::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'academic_year_id' => $year->id,
                        'semester_number' => $semester,
                        'day_of_week' => $day,
                        'start_time' => $slot['start'],
                        'end_time' => $slot['end'],
                    ],
                    [
                        'faculty_member_id' => $faculty->id,
                        'room_id' => $room->id,
                        'activity_type_id' => $activity->id,
                        'starts_on' => $start->toDateString(),
                        'ends_on' => $end->toDateString(),
                        'recurrence_pattern' => ['weekly' => true],
                    ]
                );
            }
        }
    }
}

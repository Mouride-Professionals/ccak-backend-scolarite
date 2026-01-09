<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\FacultyMember;
use App\Models\TeachingAssignment;
use Illuminate\Database\Seeder;

class TeachingAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $years = AcademicYear::all();
        if ($years->isEmpty()) {
            $this->call(AcademicYearSeeder::class);
            $years = AcademicYear::all();
        }

        $courses = Course::all();
        if ($courses->isEmpty()) {
            $this->call(CourseSeeder::class);
            $courses = Course::all();
        }

        $facultyMembers = FacultyMember::all();
        if ($facultyMembers->isEmpty()) {
            $this->call(FacultyMemberSeeder::class);
            $facultyMembers = FacultyMember::all();
        }

        foreach ($years as $year) {
            foreach ($courses->take(10) as $course) {
                $faculty = $facultyMembers->random();
                $role = collect(['TITULAR', 'TD', 'TP'])->random();
                $hours = $role === 'TITULAR' ? 36 : 24;

                TeachingAssignment::firstOrCreate(
                    [
                        'faculty_member_id' => $faculty->id,
                        'course_id' => $course->id,
                        'academic_year_id' => $year->id,
                    ],
                    [
                        'role' => $role,
                        'hours_assigned' => $hours,
                        'hourly_rate' => $role === 'TITULAR' ? 12000 : 8000,
                    ]
                );
            }
        }
    }
}

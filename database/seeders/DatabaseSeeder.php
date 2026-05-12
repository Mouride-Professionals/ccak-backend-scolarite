<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            CcakReferentialSeeder::class,  // seeds: degree_cycles, levels, faculties, departments, programs, academic_years
            CourseUnitSeeder::class,
            CourseSeeder::class,
            AdminSeeder::class,
            UserSeeder::class,
            StudentSeeder::class,
            EnrollmentSeeder::class,
            CourseEnrollmentSeeder::class,
            FacultyMemberSeeder::class,
            GradeSeeder::class,
            SemesterResultSeeder::class,
            // DocumentSeeder::class,
            // GeneratedDocumentSeeder::class,
            ActivityTypeSeeder::class,
            RoomSeeder::class,
            AcademicCalendarSeeder::class,
            HolidaySeeder::class,
            ScheduleSeeder::class,
            TeachingAssignmentSeeder::class,
            // FacultyDocumentSeeder::class,
            FacultyContractSeeder::class,
            CourseLogSeeder::class,
            AttendanceRecordSeeder::class,
            EvaluationSeeder::class,
            EvaluationResponseSeeder::class,
            DeliberationSessionSeeder::class,
            DeliberationResultSeeder::class,
            NotificationSeeder::class,
        ]);

        $admin = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $admin->assignRole('ADMIN');
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
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
            HecMaquetteSeeder::class,      // seeds real HEC course units (UE) and courses (ECUE) from maquette
            // CourseUnitSeeder::class,     // fake
            // CourseSeeder::class,         // fake
            AdminSeeder::class,
            // UserSeeder::class,           // fake
            // StudentSeeder::class,        // fake
            // EnrollmentSeeder::class,     // fake
            // CourseEnrollmentSeeder::class, // fake
            // FacultyMemberSeeder::class,  // fake
            // GradeSeeder::class,          // fake
            // SemesterResultSeeder::class, // fake
            // DocumentSeeder::class,       // fake
            // GeneratedDocumentSeeder::class, // fake
            // ActivityTypeSeeder::class,   // fake
            // RoomSeeder::class,           // fake
            // AcademicCalendarSeeder::class, // fake
            // HolidaySeeder::class,        // fake
            // ScheduleSeeder::class,       // fake
            // TeachingAssignmentSeeder::class, // fake
            // FacultyDocumentSeeder::class, // fake
            // FacultyContractSeeder::class, // fake
            // CourseLogSeeder::class,      // fake
            // AttendanceRecordSeeder::class, // fake
            // EvaluationSeeder::class,     // fake
            // EvaluationResponseSeeder::class, // fake
            // DeliberationSessionSeeder::class, // fake
            // DeliberationResultSeeder::class, // fake
            // NotificationSeeder::class,   // fake
        ]);

       
    }
}

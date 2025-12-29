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
           AcademicYearSeeder::class,
            CourseSeeder::class,
            CourseEnrollmentSeeder::class,
            GradeSeeder::class,
            SemesterResultSeeder::class,
          UserSeeder::class,
          DocumentSeeder::class,
          GeneratedDocumentSeeder::class
            DeliberationSessionSeeder::class,
            DeliberationResultSeeder::class,
            FacultyMemberSeeder::class,
            StudentSeeder::class,
        ]);

        $admin = User::factory()->create([
            'email' => 'test@example.com',
            'user_type' => 'ADMIN',
        ]);

        $admin->assignRole('ADMIN');

      
       
    }
}

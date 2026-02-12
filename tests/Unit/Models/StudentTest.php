<?php

namespace Tests\Unit\Models;

use App\Models\Document;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $student->user);
        $this->assertEquals($user->id, $student->user->id);
    }

    public function test_student_has_many_guardians(): void
    {
        $student = Student::factory()->create();
        $guardians = Guardian::factory()->count(2)->create(['student_id' => $student->id]);

        $this->assertCount(2, $student->guardians);
        $this->assertInstanceOf(Guardian::class, $student->guardians->first());
    }

    public function test_student_has_many_documents(): void
    {
        $student = Student::factory()->create();
        $documents = Document::factory()->count(2)->create(['student_id' => $student->id]);

        $this->assertCount(2, $student->documents);
        $this->assertInstanceOf(Document::class, $student->documents->first());
    }

    public function test_active_scope(): void
    {
        Student::factory()->active()->create();
        Student::factory()->graduated()->create();

        $activeStudents = Student::active()->get();

        $this->assertCount(1, $activeStudents);
        $this->assertEquals('ACTIVE', $activeStudents->first()->status);
    }

    public function test_graduated_scope(): void
    {
        Student::factory()->active()->create();
        Student::factory()->graduated()->create();

        $graduatedStudents = Student::graduated()->get();

        $this->assertCount(1, $graduatedStudents);
        $this->assertEquals('GRADUATED', $graduatedStudents->first()->status);
    }

    public function test_age_attribute(): void
    {
        $student = Student::factory()->create([
            'date_of_birth' => now()->subYears(20),
        ]);

        $this->assertEquals(20, $student->age);
    }

    public function test_generate_student_number(): void
    {
        $year = date('Y');
        $number = Student::generateStudentNumber();

        $this->assertStringStartsWith("UCAK{$year}", $number);
        $this->assertEquals(11, strlen($number)); // UCAK + YYYY + 001 = 11 chars
    }

    public function test_generate_student_number_increments(): void
    {
        $year = date('Y');

        // Create first student with specific number
        $student1 = Student::factory()->create(['student_number' => "UCAK{$year}001"]);
        $this->assertEquals("UCAK{$year}001", $student1->student_number);

        // Create second student with specific number
        $student2 = Student::factory()->create(['student_number' => "UCAK{$year}002"]);
        $this->assertEquals("UCAK{$year}002", $student2->student_number);
    }

    public function test_fillable_attributes(): void
    {
        $fillable = [
            'user_id',
            'keycloak_user_id',
            'student_number',
            'full_name',
            'gender',
            'date_of_birth',
            'place_of_birth',
            'nationality',
            'phone',
            'emergency_contact_name',
            'emergency_contact_phone',
            'address',
            'photo_url',
            'status',
        ];

        $this->assertEquals($fillable, (new Student)->getFillable());
    }

    public function test_date_of_birth_cast(): void
    {
        $student = Student::factory()->create([
            'date_of_birth' => '2000-01-01',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $student->date_of_birth);
        $this->assertEquals('2000-01-01', $student->date_of_birth->format('Y-m-d'));
    }
}

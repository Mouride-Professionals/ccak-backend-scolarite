<?php

namespace Tests\Unit\Services;

use App\Models\Student;
use App\Services\Student\StudentNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudentNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StudentNumberService;
    }

    public function test_generate_creates_unique_student_number(): void
    {
        $year = date('Y');
        $number = Student::generateStudentNumber();

        $this->assertStringStartsWith("UCAK{$year}", $number);
        $this->assertEquals(11, strlen($number));
    }

    public function test_generate_increments_numbers(): void
    {
        $year = date('Y');

        // Create existing students to test increment
        Student::factory()->create(['student_number' => "UCAK{$year}001"]);

        $number2 = Student::generateStudentNumber();
        $this->assertEquals("UCAK{$year}002", $number2);

        Student::factory()->create(['student_number' => "UCAK{$year}002"]);

        $number3 = Student::generateStudentNumber();
        $this->assertEquals("UCAK{$year}003", $number3);
    }

    public function test_generate_handles_existing_students(): void
    {
        $year = date('Y');

        // Create existing students
        Student::factory()->create(['student_number' => "UCAK{$year}001"]);
        Student::factory()->create(['student_number' => "UCAK{$year}002"]);

        $number = Student::generateStudentNumber();
        $this->assertEquals("UCAK{$year}003", $number);
    }

    public function test_generate_handles_gaps_in_sequence(): void
    {
        $year = date('Y');

        // Create students with gaps
        Student::factory()->create(['student_number' => "UCAK{$year}001"]);
        Student::factory()->create(['student_number' => "UCAK{$year}003"]);

        $number = Student::generateStudentNumber();
        $this->assertEquals("UCAK{$year}004", $number);
    }

    public function test_generate_handles_year_change(): void
    {
        $year = date('Y');

        // Create student from previous year
        Student::factory()->create(['student_number' => 'UCAK'.($year - 1).'001']);

        $number = Student::generateStudentNumber();
        $this->assertEquals("UCAK{$year}001", $number);
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardian_belongs_to_student(): void
    {
        $student = Student::factory()->create();
        $guardian = Guardian::factory()->create(['student_id' => $student->id]);

        $this->assertInstanceOf(Student::class, $guardian->student);
        $this->assertEquals($student->id, $guardian->student->id);
    }

    public function test_fathers_scope(): void
    {
        Guardian::factory()->father()->create();
        Guardian::factory()->mother()->create();

        $fathers = Guardian::fathers()->get();

        $this->assertCount(1, $fathers);
        $this->assertEquals('FATHER', $fathers->first()->relationship);
    }

    public function test_mothers_scope(): void
    {
        Guardian::factory()->father()->create();
        Guardian::factory()->mother()->create();

        $mothers = Guardian::mothers()->get();

        $this->assertCount(1, $mothers);
        $this->assertEquals('MOTHER', $mothers->first()->relationship);
    }

    public function test_guardians_scope(): void
    {
        Guardian::factory()->father()->create();
        Guardian::factory()->guardian()->create();

        $guardians = Guardian::guardians()->get();

        $this->assertCount(1, $guardians);
        $this->assertEquals('GUARDIAN', $guardians->first()->relationship);
    }

    public function test_fillable_attributes(): void
    {
        $fillable = [
            'student_id',
            'first_name',
            'last_name',
            'full_name',
            'relationship',
            'phone',
            'phone_2',
            'email',
            'address',
            'occupation',
        ];

        $this->assertEquals($fillable, (new Guardian)->getFillable());
    }
}

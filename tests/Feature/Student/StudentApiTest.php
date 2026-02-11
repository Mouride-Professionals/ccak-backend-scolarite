<?php

namespace Tests\Feature\Student;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class StudentApiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    private array $permissions = [
        'students.view',
        'students.create',
        'students.update',
        'students.delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_list_students(): void
    {
        $this->actingAsUserWithPermissions(['students.view']);

        Student::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/students');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_students_by_status(): void
    {
        $this->actingAsUserWithPermissions(['students.view']);

        Student::factory()->active()->create();
        Student::factory()->graduated()->create();

        $response = $this->getJson('/api/v1/students?status=ACTIVE');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_students_by_name(): void
    {
        $this->actingAsUserWithPermissions(['students.view']);

        Student::factory()->create(['full_name' => 'John Doe']);
        Student::factory()->create(['full_name' => 'Jane Smith']);

        $response = $this->getJson('/api/v1/students?name=John');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_students_by_student_number(): void
    {
        $this->actingAsUserWithPermissions(['students.view']);

        Student::factory()->create(['student_number' => 'UCAK2024001']);
        Student::factory()->create(['student_number' => 'UCAK2024002']);

        $response = $this->getJson('/api/v1/students?student_number=UCAK2024001');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_sort_students(): void
    {
        $this->actingAsUserWithPermissions(['students.view']);

        Student::factory()->create(['full_name' => 'Zoe']);
        Student::factory()->create(['full_name' => 'Alice']);

        $response = $this->getJson('/api/v1/students?sort=full_name');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.full_name', 'Alice')
            ->assertJsonPath('data.1.full_name', 'Zoe');
    }

    public function test_can_paginate_students(): void
    {
        $this->actingAsUserWithPermissions(['students.view']);

        Student::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/students?per_page=2');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    }

    public function test_can_create_student(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $user = User::factory()->create();

        $payload = [
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'gender' => 'M',
            'date_of_birth' => '2000-01-01',
            'place_of_birth' => 'Dakar',
            'nationality' => 'Sénégalaise',
            'phone' => '+221771234567',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '+221771234568',
            'address' => '123 Main St, Dakar',
            'photo_url' => 'https://example.com/photo.jpg',
            'status' => 'ACTIVE',
        ];

        $response = $this->postJson('/api/v1/students', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.full_name', 'John Doe')
            ->assertJsonPath('data.status', 'ACTIVE');

        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_cannot_create_student_with_existing_user(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $user = User::factory()->create();
        Student::factory()->create(['user_id' => $user->id]);

        $payload = [
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'gender' => 'M',
            'date_of_birth' => '2000-01-01',
            'place_of_birth' => 'Dakar',
            'nationality' => 'Sénégalaise',
            'phone' => '+221771234567',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '+221771234568',
            'address' => '123 Main St, Dakar',
        ];

        $response = $this->postJson('/api/v1/students', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_can_show_student(): void
    {
        $this->actingAsUserWithPermissions(['students.view']);

        $student = Student::factory()->create();

        $response = $this->getJson("/api/v1/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $student->id)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'user',
                    'guardians',
                    'documents',
                ],
            ]);
    }

    public function test_can_update_student(): void
    {
        $this->actingAsUserWithPermissions(['students.update']);

        $student = Student::factory()->create();

        $payload = [
            'full_name' => 'Updated Name',
            'phone' => '+221779876543',
        ];

        $response = $this->putJson("/api/v1/students/{$student->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.full_name', 'Updated Name')
            ->assertJsonPath('data.phone', '+221779876543');

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'full_name' => 'Updated Name',
            'phone' => '+221779876543',
        ]);
    }

    public function test_students_require_permission(): void
    {
        $this->seedPermissions($this->permissions);

        $student = Student::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->getJson('/api/v1/students')->assertStatus(403);
        $this->postJson('/api/v1/students', [])->assertStatus(403);
        $this->putJson("/api/v1/students/{$student->id}", [])->assertStatus(403);
    }

    public function test_store_student_validation(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        // Test required fields
        $this->postJson('/api/v1/students', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'user_id',
                'full_name',
                'gender',
                'date_of_birth',
                'place_of_birth',
                'nationality',
                'phone',
                'emergency_contact_name',
                'emergency_contact_phone',
                'address',
            ]);

        // Test invalid user_id
        $this->postJson('/api/v1/students', [
            'user_id' => 'invalid-uuid',
            'full_name' => 'John Doe',
            'gender' => 'M',
            'date_of_birth' => '2000-01-01',
            'place_of_birth' => 'Dakar',
            'nationality' => 'Sénégalaise',
            'phone' => '+221771234567',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '+221771234568',
            'address' => '123 Main St, Dakar',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);

        // Test invalid gender
        $user = User::factory()->create();
        $this->postJson('/api/v1/students', [
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'gender' => 'X',
            'date_of_birth' => '2000-01-01',
            'place_of_birth' => 'Dakar',
            'nationality' => 'Sénégalaise',
            'phone' => '+221771234567',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '+221771234568',
            'address' => '123 Main St, Dakar',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);

        // Test future date of birth
        $this->postJson('/api/v1/students', [
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'gender' => 'M',
            'date_of_birth' => '2030-01-01',
            'place_of_birth' => 'Dakar',
            'nationality' => 'Sénégalaise',
            'phone' => '+221771234567',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '+221771234568',
            'address' => '123 Main St, Dakar',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['date_of_birth']);

        // Test invalid status
        $this->postJson('/api/v1/students', [
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'gender' => 'M',
            'date_of_birth' => '2000-01-01',
            'place_of_birth' => 'Dakar',
            'nationality' => 'Sénégalaise',
            'phone' => '+221771234567',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '+221771234568',
            'address' => '123 Main St, Dakar',
            'status' => 'INVALID_STATUS',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_update_student_validation(): void
    {
        $this->actingAsUserWithPermissions(['students.update']);

        $student = Student::factory()->create();

        // Test invalid gender
        $this->putJson("/api/v1/students/{$student->id}", [
            'gender' => 'X',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);

        // Test future date of birth
        $this->putJson("/api/v1/students/{$student->id}", [
            'date_of_birth' => '2030-01-01',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['date_of_birth']);

        // Test invalid status
        $this->putJson("/api/v1/students/{$student->id}", [
            'status' => 'INVALID_STATUS',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}

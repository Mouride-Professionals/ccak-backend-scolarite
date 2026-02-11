<?php

namespace Tests\Feature\Student;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class GuardianApiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    private array $permissions = [
        'guardians.view',
        'guardians.create',
        'guardians.update',
        'guardians.delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_list_guardians_for_student(): void
    {
        $this->actingAsUserWithPermissions(['guardians.view']);

        $student = Student::factory()->create();
        Guardian::factory()->count(3)->create(['student_id' => $student->id]);

        $response = $this->getJson("/api/v1/students/{$student->id}/guardians");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_guardians_by_relationship(): void
    {
        $this->actingAsUserWithPermissions(['guardians.view']);

        $student = Student::factory()->create();
        Guardian::factory()->father()->create(['student_id' => $student->id]);
        Guardian::factory()->mother()->create(['student_id' => $student->id]);

        $response = $this->getJson("/api/v1/students/{$student->id}/guardians?filter[relationship]=FATHER");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_guardians_by_name(): void
    {
        $this->actingAsUserWithPermissions(['guardians.view']);

        $student = Student::factory()->create();
        Guardian::factory()->create(['student_id' => $student->id, 'full_name' => 'John Doe']);
        Guardian::factory()->create(['student_id' => $student->id, 'full_name' => 'Jane Smith']);

        $response = $this->getJson("/api/v1/students/{$student->id}/guardians?filter[full_name]=John");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_sort_guardians(): void
    {
        $this->actingAsUserWithPermissions(['guardians.view']);

        $student = Student::factory()->create();
        Guardian::factory()->create(['student_id' => $student->id, 'full_name' => 'Zoe']);
        Guardian::factory()->create(['student_id' => $student->id, 'full_name' => 'Alice']);

        $response = $this->getJson("/api/v1/students/{$student->id}/guardians?sort=full_name");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.full_name', 'Alice')
            ->assertJsonPath('data.1.full_name', 'Zoe');
    }

    public function test_can_paginate_guardians(): void
    {
        $this->actingAsUserWithPermissions(['guardians.view']);

        $student = Student::factory()->create();
        Guardian::factory()->count(5)->create(['student_id' => $student->id]);

        $response = $this->getJson("/api/v1/students/{$student->id}/guardians?per_page=2");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    }

    public function test_can_create_guardian(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $student = Student::factory()->create();

        $payload = [
            'full_name' => 'John Doe',
            'relationship' => 'FATHER',
            'phone' => '+221771234567',
            'email' => 'john@example.com',
            'address' => '123 Main St, Dakar',
            'occupation' => 'Engineer',
        ];

        $response = $this->postJson("/api/v1/students/{$student->id}/guardians", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.full_name', 'John Doe')
            ->assertJsonPath('data.relationship', 'FATHER');

        $this->assertDatabaseHas('guardians', [
            'student_id' => $student->id,
            'full_name' => 'John Doe',
            'relationship' => 'FATHER',
        ]);
    }

    public function test_can_show_guardian(): void
    {
        $this->actingAsUserWithPermissions(['guardians.view']);

        $student = Student::factory()->create();
        $guardian = Guardian::factory()->create(['student_id' => $student->id]);

        $response = $this->getJson("/api/v1/students/{$student->id}/guardians/{$guardian->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $guardian->id)
            ->assertJsonPath('data.student_id', $student->id);
    }

    public function test_cannot_show_guardian_from_different_student(): void
    {
        $this->actingAsUserWithPermissions(['guardians.view']);

        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        $guardian = Guardian::factory()->create(['student_id' => $student1->id]);

        $response = $this->getJson("/api/v1/students/{$student2->id}/guardians/{$guardian->id}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_guardian(): void
    {
        $this->actingAsUserWithPermissions(['guardians.update']);

        $student = Student::factory()->create();
        $guardian = Guardian::factory()->create(['student_id' => $student->id]);

        $payload = [
            'full_name' => 'Updated Name',
            'phone' => '+221779876543',
        ];

        $response = $this->putJson("/api/v1/students/{$student->id}/guardians/{$guardian->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.full_name', 'Updated Name')
            ->assertJsonPath('data.phone', '+221779876543');

        $this->assertDatabaseHas('guardians', [
            'id' => $guardian->id,
            'full_name' => 'Updated Name',
            'phone' => '+221779876543',
        ]);
    }

    public function test_cannot_update_guardian_from_different_student(): void
    {
        $this->actingAsUserWithPermissions(['guardians.update']);

        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        $guardian = Guardian::factory()->create(['student_id' => $student1->id]);

        $response = $this->putJson("/api/v1/students/{$student2->id}/guardians/{$guardian->id}", [
            'full_name' => 'Updated Name',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_delete_guardian(): void
    {
        $this->actingAsUserWithPermissions(['guardians.delete']);

        $student = Student::factory()->create();
        $guardian = Guardian::factory()->create(['student_id' => $student->id]);

        $response = $this->deleteJson("/api/v1/students/{$student->id}/guardians/{$guardian->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('guardians', [
            'id' => $guardian->id,
        ]);
    }

    public function test_cannot_delete_guardian_from_different_student(): void
    {
        $this->actingAsUserWithPermissions(['guardians.delete']);

        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        $guardian = Guardian::factory()->create(['student_id' => $student1->id]);

        $response = $this->deleteJson("/api/v1/students/{$student2->id}/guardians/{$guardian->id}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_guardians_require_permission(): void
    {
        $this->seedPermissions($this->permissions);

        $student = Student::factory()->create();
        $guardian = Guardian::factory()->create(['student_id' => $student->id]);
        $this->actingAs(User::factory()->create());

        $this->getJson("/api/v1/students/{$student->id}/guardians")->assertStatus(403);
        $this->postJson("/api/v1/students/{$student->id}/guardians", [])->assertStatus(403);
        $this->putJson("/api/v1/students/{$student->id}/guardians/{$guardian->id}", [])->assertStatus(403);
        $this->deleteJson("/api/v1/students/{$student->id}/guardians/{$guardian->id}")->assertStatus(403);
    }

    public function test_store_guardian_validation(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $student = Student::factory()->create();

        // Test required fields
        $this->postJson("/api/v1/students/{$student->id}/guardians", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'full_name',
                'relationship',
                'phone',
                'email',
                'address',
                'occupation',
            ]);

        // Test invalid relationship
        $this->postJson("/api/v1/students/{$student->id}/guardians", [
            'full_name' => 'John Doe',
            'relationship' => 'INVALID',
            'phone' => '+221771234567',
            'email' => 'john@example.com',
            'address' => '123 Main St',
            'occupation' => 'Engineer',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['relationship']);

        // Test invalid email
        $this->postJson("/api/v1/students/{$student->id}/guardians", [
            'full_name' => 'John Doe',
            'relationship' => 'FATHER',
            'phone' => '+221771234567',
            'email' => 'invalid-email',
            'address' => '123 Main St',
            'occupation' => 'Engineer',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test duplicate email
        Guardian::factory()->create(['email' => 'john@example.com']);
        $this->postJson("/api/v1/students/{$student->id}/guardians", [
            'full_name' => 'Jane Doe',
            'relationship' => 'MOTHER',
            'phone' => '+221771234568',
            'email' => 'john@example.com',
            'address' => '123 Main St',
            'occupation' => 'Teacher',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_guardian_validation(): void
    {
        $this->actingAsUserWithPermissions(['guardians.update']);

        $student = Student::factory()->create();
        $guardian = Guardian::factory()->create(['student_id' => $student->id]);

        // Test invalid relationship
        $this->putJson("/api/v1/students/{$student->id}/guardians/{$guardian->id}", [
            'relationship' => 'INVALID',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['relationship']);

        // Test invalid email
        $this->putJson("/api/v1/students/{$student->id}/guardians/{$guardian->id}", [
            'email' => 'invalid-email',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test duplicate email (should allow same email for same guardian)
        $this->putJson("/api/v1/students/{$student->id}/guardians/{$guardian->id}", [
            'email' => $guardian->email,
        ])->assertStatus(200);

        // Test duplicate email for different guardian
        $otherGuardian = Guardian::factory()->create(['email' => 'other@example.com']);
        $this->putJson("/api/v1/students/{$student->id}/guardians/{$guardian->id}", [
            'email' => 'other@example.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}

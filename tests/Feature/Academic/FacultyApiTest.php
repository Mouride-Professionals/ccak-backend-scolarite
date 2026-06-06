<?php

namespace Tests\Feature\Academic;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class FacultyApiTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    private array $permissions = [
        'faculties.view',
        'faculties.create',
        'faculties.update',
        'faculties.delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_crud_faculties(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $dean = User::factory()->create();

        $payload = [
            'name' => 'Engineering',
            'code' => 'ENG',
            'dean_id' => $dean->id,
            'is_active' => true,
        ];

        $createResponse = $this->postJson('/api/v1/faculties', $payload);
        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Engineering')
            ->assertJsonPath('data.code', 'ENG')
            ->assertJsonPath('data.dean_id', $dean->id);

        $facultyId = $createResponse->json('data.id');

        $this->assertDatabaseHas('faculties', [
            'id' => $facultyId,
            'code' => 'ENG',
        ]);

        $this->getJson('/api/v1/faculties')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/faculties/{$facultyId}")
            ->assertOk()
            ->assertJsonPath('data.id', $facultyId);

        $updatePayload = [
            'name' => 'Engineering & Technology',
            'code' => 'ENGT',
        ];

        $this->putJson("/api/v1/faculties/{$facultyId}", $updatePayload)
            ->assertOk()
            ->assertJsonPath('data.name', 'Engineering & Technology')
            ->assertJsonPath('data.code', 'ENGT');

        $this->deleteJson("/api/v1/faculties/{$facultyId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('faculties', [
            'id' => $facultyId,
        ]);
    }

    public function test_faculty_requires_name_and_code(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $this->postJson('/api/v1/faculties', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code']);
    }

    public function test_faculty_requires_permission(): void
    {
        $this->seedPermissions($this->permissions);

        $this->actingAs(User::factory()->create());

        $this->getJson('/api/v1/faculties')
            ->assertStatus(403);
    }
}

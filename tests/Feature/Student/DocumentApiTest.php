<?php

namespace Tests\Feature\Student;

use App\Models\Admin;
use App\Models\Document;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    private array $permissions = [
        'documents.view',
        'documents.create',
        'documents.update',
        'documents.delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
        Storage::fake('documents');
    }

    public function test_can_list_documents_for_student(): void
    {
        $this->actingAsUserWithPermissions(['documents.view']);

        $student = Student::factory()->create();
        Document::factory()->count(3)->create(['student_id' => $student->id]);

        $response = $this->getJson("/api/v1/students/{$student->id}/documents");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.data');
    }

    public function test_can_show_document(): void
    {
        $this->actingAsUserWithPermissions(['documents.view']);

        $student = Student::factory()->create();
        $document = Document::factory()->create(['student_id' => $student->id]);

        $response = $this->getJson("/api/v1/students/{$student->id}/documents/{$document->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $document->id)
            ->assertJsonPath('data.student_id', $student->id);
    }

    public function test_can_upload_document(): void
    {
        $this->actingAsUserWithPermissions(['documents.create']);

        $student = Student::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $payload = [
            'document' => $file,
            'type' => 'CNI',
        ];

        $response = $this->postJson("/api/v1/students/{$student->id}/documents", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'CNI')
            ->assertJsonPath('data.status', 'PENDING');

        $this->assertDatabaseHas('documents', [
            'student_id' => $student->id,
            'type' => 'CNI',
            'status' => 'PENDING',
        ]);

        // Vérifier que le fichier a été stocké
        $this->assertTrue(Storage::disk('documents')->exists($response->json('data.file_path')));
    }

    public function test_can_review_document(): void
    {
        $user = $this->actingAsUserWithPermissions(['documents.update']);
        $admin = Admin::factory()->create(['user_id' => $user->id]);

        $student = Student::factory()->create();
        $document = Document::factory()->pending()->create(['student_id' => $student->id]);

        $payload = [
            'status' => 'APPROVED',
            'notes' => 'Document approuvé',
        ];

        $response = $this->putJson("/api/v1/documents/{$document->id}/review", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'APPROVED')
            ->assertJsonPath('data.notes', 'Document approuvé');

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status' => 'APPROVED',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_cannot_review_already_reviewed_document(): void
    {
        $user = $this->actingAsUserWithPermissions(['documents.update']);
        Admin::factory()->create(['user_id' => $user->id]);

        $document = Document::factory()->approved()->create();

        $response = $this->putJson("/api/v1/documents/{$document->id}/review", [
            'status' => 'REJECTED',
            'notes' => 'Test',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_upload_document_validation(): void
    {
        $this->actingAsUserWithPermissions(['documents.create']);

        $student = Student::factory()->create();

        // Test required fields
        $this->postJson("/api/v1/students/{$student->id}/documents", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['document', 'type']);

        // Test invalid file type
        $file = UploadedFile::fake()->create('document.txt', 1000, 'text/plain');
        $this->postJson("/api/v1/students/{$student->id}/documents", [
            'document' => $file,
            'type' => 'CNI',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['document']);

        // Test file too large
        $largeFile = UploadedFile::fake()->create('large.pdf', 6000, 'application/pdf'); // 6MB
        $this->postJson("/api/v1/students/{$student->id}/documents", [
            'document' => $largeFile,
            'type' => 'CNI',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['document']);

        // Test invalid type
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');
        $this->postJson("/api/v1/students/{$student->id}/documents", [
            'document' => $file,
            'type' => 'INVALID_TYPE',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_review_document_validation(): void
    {
        $user = $this->actingAsUserWithPermissions(['documents.update']);
        Admin::factory()->create(['user_id' => $user->id]);

        $document = Document::factory()->pending()->create();

        // Test required status
        $this->putJson("/api/v1/documents/{$document->id}/review", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Test invalid status
        $this->putJson("/api/v1/documents/{$document->id}/review", [
            'status' => 'INVALID',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Test notes too long
        $this->putJson("/api/v1/documents/{$document->id}/review", [
            'status' => 'APPROVED',
            'notes' => str_repeat('a', 1001),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['notes']);
    }

    public function test_documents_require_permission(): void
    {
        $this->seedPermissions($this->permissions);

        $student = Student::factory()->create();
        $document = Document::factory()->pending()->create(['student_id' => $student->id]);
        $this->actingAs(User::factory()->create());

        $this->getJson("/api/v1/students/{$student->id}/documents")->assertStatus(403);
        $this->postJson("/api/v1/students/{$student->id}/documents", [])->assertStatus(403);
        $this->putJson("/api/v1/documents/{$document->id}/review", [])->assertStatus(403);
    }

    public function test_review_requires_authorization(): void
    {
        $this->actingAsUserWithPermissions(['documents.update']);

        $document = Document::factory()->pending()->create();
        $user = User::factory()->create(); // User without admin profile
        $this->actingAs($user);

        $this->putJson("/api/v1/documents/{$document->id}/review", [
            'status' => 'APPROVED',
        ])->assertStatus(403);
    }
}

<?php

namespace Tests\Feature\Document;

use App\Models\GeneratedDocument;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class GeneratedDocumentApiTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    private array $permissions = [
        'generated_documents.view',
        'generated_documents.create',
        'generated_documents.update',
        'generated_documents.delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Authenticate::class);
    }

    public function test_can_crud_generated_documents(): void
    {
        $this->actingAsUserWithPermissions($this->permissions);

        $student = Student::factory()->create();
        $user = User::factory()->create();

        $payload = [
            'type' => GeneratedDocument::TYPE_TRANSCRIPT, // ex: 'TRANSCRIPT'
            'student_id' => $student->id,
            'document_number' => 'DOC-'.strtoupper(Str::random(8)),
            'metadata' => [
                'student_name' => 'John Doe',
                'student_number' => '2024-0001',
                // autres champs attendus par le template...
            ],
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'issued_at' => null, // ou Carbon::now()->format('Y-m-d H:i:s') si émis
            'status' => GeneratedDocument::STATUS_REVOKED,
            'generated_by' => $user->id,
            'file_path' => '/documents/generated/doc-'.strtolower(Str::random(8)).'.pdf',
        ];

        $createResponse = $this->postJson('/api/v1/generated-documents', $payload);
        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', GeneratedDocument::TYPE_TRANSCRIPT)
            ->assertJsonPath('data.student_id', $payload['student_id']);

        $documentId = $createResponse->json('data.id');

        $this->assertDatabaseHas('generated_documents', [
            'id' => $documentId,
            'type' => GeneratedDocument::TYPE_TRANSCRIPT,
        ]);

        $this->getJson('/api/v1/generated-documents')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/generated-documents/{$documentId}")
            ->assertOk()
            ->assertJsonPath('data.id', $documentId);

        // dd($createResponse->json());
        $updateResponse = $this->putJson("/api/v1/generated-documents/{$documentId}", [
            'status' => GeneratedDocument::STATUS_ISSUED,
            'issued_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.status', GeneratedDocument::STATUS_ISSUED);

        $documentNumber = $createResponse->json('data.document_number');
        $this->getJson("/api/v1/generated-documents/verify/{$documentNumber}")
            ->assertOk()
            ->assertJsonPath('data.document_number', $documentNumber);

        $this->deleteJson("/api/v1/generated-documents/{$documentId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('generated_documents', [
            'id' => $documentId,
        ]);
    }
}

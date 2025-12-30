<?php

namespace Tests\Feature\Document;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Admin;
use App\Models\Document;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    private Student $student;

    private array $permissions = [
        'documents.view',
        'documents.create',
        'documents.update',
        'documents.delete',
        'documents.review',
        'documents.download',
    ];


    protected function setUp(): void
    {
        parent::setUp();
        $this->student = Student::factory()->create();
        Storage::fake('documents');
        $user = $this->actingAsUserWithPermissions($this->permissions);
        $this->actingAs($user, 'api');
    }

    #[Test]
    public function it_can_list_documents(): void
    {
        // Créer quelques documents
        Document::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/documents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'student_id',
                        'file_name',
                    ]
                ],
            ]);
    }

    #[Test]
    public function it_can_filter_documents_by_student(): void
    {
        // Créer un autre étudiant
        $anotherStudent = Student::factory()->create();

        // Documents pour le premier étudiant
        Document::factory()->count(2)->create([
            'student_id' => $this->student->id,
        ]);

        // Documents pour l'autre étudiant
        Document::factory()->count(1)->create([
            'student_id' => $anotherStudent->id,
        ]);

        $response = $this->getJson("/api/v1/documents/student/{$this->student->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }


    #[Test]
    public function it_can_upload_a_new_document(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $payload = [
            'student_id' => $this->student->id,
            'type' => DocumentType::CNI->value,
            'notes' => 'Document d\'identité',
            'document_file' => $file,
        ];

        $response = $this->post('/api/v1/documents', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.student_id', $this->student->id)
            ->assertJsonPath('data.type', DocumentType::CNI->value)
            ->assertJsonPath('data.status', DocumentStatus::PENDING->value);

        // Vérifier que le fichier a été stocké
        $document = Document::first();
        $this->assertTrue(Storage::disk('documents')->exists($document->file_path));
    }

    #[Test]
    public function it_validates_file_on_upload(): void
    {
        // Fichier trop gros
        $file = UploadedFile::fake()->create('document.pdf', 6000, 'application/pdf');

        $payload = [
            'student_id' => $this->student->id,
            'type' => DocumentType::CNI->value,
            'document_file' => $file,
        ];

        $response = $this->postJson('/api/v1/documents', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_file']);
    }

    #[Test]
    public function it_can_show_a_document(): void
    {
        $document = Document::factory()->create([
            'student_id' => $this->student->id,
        ]);

        $response = $this->getJson("/api/v1/documents/$document->id");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'file_name',
                    'student_id',
                ]
            ])
            ->assertJsonPath('data.id', $document->id);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_document(): void
    {
        $response = $this->getJson('/api/v1/documents/nonexistent-id');
        $response->assertStatus(404);
    }


    #[Test]
    public function student_cannot_update_approved_document(): void
    {
        $document = Document::factory()->approved()->create([
            'student_id' => $this->student->id,
        ]);

        $payload = [
            'notes' => 'Tentative de modification',
        ];

        $response = $this->putJson("/api/v1/documents/$document->id", $payload);
        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_approve_a_document(): void
    {
        $document = Document::factory()->pending()->create([
            'student_id' => $this->student->id,
        ]);

        $payload = [
            'notes' => 'Document approuvé',
        ];

        $response = $this->postJson("/api/v1/documents/$document->id/approve", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', DocumentStatus::APPROVED->value);
    }

    #[Test]
    public function admin_cannot_approve_already_approved_document(): void
    {
        $admin = Admin::factory()->create();
        $document = Document::factory()->approved()->create([
            'student_id' => $this->student->id,
            'reviewed_by' => $admin->id,
        ]);

        $response = $this->postJson("/api/v1/documents/$document->id/approve");

        $response->assertStatus(400);
    }

    #[Test]
    public function admin_can_reject_a_document(): void
    {


        $document = Document::factory()->pending()->create([
            'student_id' => $this->student->id,
        ]);

        $payload = [
            'reason' => 'Document illisible, veuillez uploader une meilleure qualité',
        ];

        $response = $this->postJson("/api/v1/documents/$document->id/reject", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', DocumentStatus::REJECTED->value);
    }

    #[Test]
    public function reject_requires_a_reason(): void
    {


        $document = Document::factory()->pending()->create([
            'student_id' => $this->student->id,
        ]);

        $response = $this->postJson("/api/v1/documents/$document->id/reject");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    #[Test]
    public function student_cannot_review_documents(): void
    {
        $this->actingAs(User::factory()->create(), 'api');

        $document = Document::factory()->pending()->create([
            'student_id' => $this->student->id,
        ]);

        $response = $this->postJson("/api/v1/documents/$document->id/approve");

        $response->assertStatus(403);
    }

    #[Test]
    public function it_can_download_document_info(): void
    {
        $document = Document::factory()->create([
            'student_id' => $this->student->id,
        ]);

        Storage::disk('documents')->put($document->file_path, 'PDF content');

        $response = $this->getJson("/api/v1/documents/$document->id/download");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'download_url',
                    'file_name',
                    'file_size',
                    'mime_type',
                ]
            ]);
    }

    #[Test]
    public function it_can_download_document_file(): void
    {


        // Créer un vrai fichier dans le storage fake
        $fileName = 'test.pdf';
        $filePath = "documents/{$this->student->id}/cni/{$fileName}";

        Storage::disk('documents')->put($filePath, 'PDF content');

        $document = Document::factory()->create([
            'student_id' => $this->student->id,
            'file_path' => $filePath,
            'file_name' => $fileName,
        ]);

        $response = $this->get("/api/v1/documents/$document->id/download-file");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', "attachment; filename={$fileName}");
    }

    #[Test]
    public function download_returns_404_for_nonexistent_file(): void
    {


        $document = Document::factory()->create([
            'student_id' => $this->student->id,
            'file_path' => 'nonexistent.pdf',
        ]);

        $response = $this->getJson("/api/v1/documents/$document->id/download");

        $response->assertStatus(404);
    }

    #[Test]
    public function admin_can_delete_any_document(): void
    {


        $document = Document::factory()->create([
            'student_id' => $this->student->id,
        ]);

        $response = $this->deleteJson("/api/v1/documents/$document->id");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('documents', [
            'id' => $document->id,
        ]);
    }

    #[Test]
    public function student_can_delete_own_pending_document(): void
    {


        $document = Document::factory()->pending()->create([
            'student_id' => $this->student->id,
        ]);

        $response = $this->deleteJson("/api/v1/documents/$document->id");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('documents', [
            'id' => $document->id,
        ]);
    }

    #[Test]
    public function student_cannot_delete_approved_document(): void
    {
        $this->actingAs(User::factory()->create(), 'api');

        $document = Document::factory()->approved()->create([
            'student_id' => $this->student->id,
        ]);

        $response = $this->deleteJson("/api/v1/documents/$document->id");

        $response->assertStatus(403);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
        ]);
    }

    #[Test]
    public function it_can_check_student_document_status(): void
    {


        // Créer quelques documents pour l'étudiant
        Document::factory()->approved()->create([
            'student_id' => $this->student->id,
            'type' => DocumentType::CNI,
        ]);

        Document::factory()->pending()->create([
            'student_id' => $this->student->id,
            'type' => DocumentType::BIRTH_CERT,
        ]);

        $response = $this->getJson("/api/v1/documents/check-status/{$this->student->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'pending',
                    'approved',
                    'rejected',
                    'by_type',
                    'required_docs_status',
                    'all_required_approved',
                ]
            ])
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.approved', 1)
            ->assertJsonPath('data.pending', 1);
    }

    #[Test]
    public function it_can_generate_report(): void
    {


        // Créer des documents pour différents statuts
        Document::factory()->pending()->count(5)->create();
        Document::factory()->approved()->count(3)->create();
        Document::factory()->rejected()->count(2)->create();

        $response = $this->getJson('/api/v1/documents/report');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'summary' => [
                        'total',
                        'by_type',
                        'by_status',
                        'pending_count',
                    ],
                    'details',
                ]
            ])
            ->assertJsonPath('data.summary.total', 10)
            ->assertJsonPath('data.summary.pending_count', 5);
    }

    #[Test]
    public function it_can_filter_report_by_date(): void
    {


        $dateFrom = now()->subDays(7)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        // Document récent
        Document::factory()->create([
            'uploaded_at' => now()->subDays(3),
        ]);

        // Document ancien (ne doit pas apparaître)
        Document::factory()->create([
            'uploaded_at' => now()->subDays(14),
        ]);

        $response = $this->getJson("/api/v1/documents/report?date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.total', 1);
    }

    #[Test]
    public function it_can_list_pending_documents(): void
    {


        Document::factory()->pending()->count(3)->create();
        Document::factory()->approved()->count(2)->create();

        $response = $this->getJson('/api/v1/documents/pending');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_can_list_student_documents(): void
    {


        // Créer un autre étudiant
        $anotherStudent = Student::factory()->create();

        // Documents pour le premier étudiant
        Document::factory()->count(4)->create([
            'student_id' => $this->student->id,
        ]);

        // Documents pour l'autre étudiant
        Document::factory()->count(2)->create([
            'student_id' => $anotherStudent->id,
        ]);

        $response = $this->getJson("/api/v1/documents/student/{$this->student->id}");

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');
    }

    #[Test]
    public function it_can_filter_student_documents_by_type(): void
    {


        Document::factory()->create([
            'student_id' => $this->student->id,
            'type' => DocumentType::CNI,
        ]);

        Document::factory()->create([
            'student_id' => $this->student->id,
            'type' => DocumentType::BIRTH_CERT,
        ]);

        $response = $this->getJson("/api/v1/documents/student/{$this->student->id}?type=CNI");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', DocumentType::CNI->value);
    }

    #[Test]
    public function it_enforces_upload_limits(): void
    {


        // Créer le maximum de documents autorisés pour un type
        $maxPerType = config('documents.max_per_type', 3);

        Document::factory()->count($maxPerType)->create([
            'student_id' => $this->student->id,
            'type' => DocumentType::CNI,
        ]);

        // Tenter d'uploader un document supplémentaire
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $payload = [
            'student_id' => $this->student->id,
            'type' => DocumentType::CNI->value,
            'document_file' => $file,
        ];

        $response = $this->postJson('/api/v1/documents', $payload);

        $response->assertStatus(400);
    }

    #[Test]
    public function it_validates_document_type_on_upload(): void
    {


        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $payload = [
            'student_id' => $this->student->id,
            'type' => 'INVALID_TYPE', // Type invalide
            'document_file' => $file,
        ];

        $response = $this->postJson('/api/v1/documents', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function it_requires_authentication_for_protected_endpoints(): void
    {
        $this->app['auth']->forgetGuards();

        // Tester sans authentification
        $response = $this->getJson('/api/v1/documents');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_enforces_permissions(): void
    {
        $this->actingAs(User::factory()->create(), 'api');

        $response = $this->getJson('/api/v1/documents');
        $response->assertStatus(403);
    }

    #[Test]
    public function it_can_upload_image_document(): void
    {


        $file = UploadedFile::fake()->image('photo.jpg', 400, 500);

        $payload = [
            'student_id' => $this->student->id,
            'type' => DocumentType::PHOTO->value,
            'notes' => 'Photo d\'identité',
            'document_file' => $file,
        ];

        $response = $this->postJson('/api/v1/documents', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', DocumentType::PHOTO->value);
    }

    #[Test]
    public function it_rejects_invalid_image_dimensions_for_photo(): void
    {


        // Image trop petite
        $file = UploadedFile::fake()->image('photo.jpg', 200, 200);

        $payload = [
            'student_id' => $this->student->id,
            'type' => DocumentType::PHOTO->value,
            'document_file' => $file,
        ];

        $response = $this->postJson('/api/v1/documents', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_file']);
    }

    #[Test]
    public function it_can_paginate_results(): void
    {


        Document::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/documents?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    #[Test]
    public function it_can_update_multiple_metadata_fields(): void
    {


        $document = Document::factory()->create();

        $payload = [
            'metadata' => [
                'extracted_data' => [
                    'name' => 'John Doe',
                    'birth_date' => '1990-01-01',
                ],
                'ocr_confidence' => 0.95,
            ],
        ];

        $response = $this->putJson("/api/v1/documents/$document->id", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.metadata.extracted_data.name', 'John Doe')
            ->assertJsonPath('data.metadata.ocr_confidence', 0.95);
    }
}

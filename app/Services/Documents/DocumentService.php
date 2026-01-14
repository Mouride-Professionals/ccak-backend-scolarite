<?php

namespace App\Services\Documents;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Repositories\DocumentRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentService
{
    private DocumentRepository $repository;
    private DocumentValidationService $validationService;
    private DocumentStorageService $storageService;
    private DocumentNotificationService $notificationService;

    public function __construct(
        DocumentRepository $repository,
        DocumentValidationService $validationService,
        DocumentStorageService $storageService,
        DocumentNotificationService $notificationService
    ) {
        $this->repository = $repository;
        $this->validationService = $validationService;
        $this->storageService = $storageService;
        $this->notificationService = $notificationService;
    }

    /**
     * Uploader un nouveau document
     */
    /** @param array<string, mixed> $data */
    public function upload(array $data, UploadedFile $file): Document
    {
        // Valider les données
        $validatedData = $this->validationService->validateUploadData($data);

        // Valider le fichier
        $this->validationService->validateFile($file, $validatedData['type']);

        // Vérifier les limites
        $this->validationService->checkUploadLimits(
            $validatedData['student_id'],
            $validatedData['type']
        );

        $documentData = array_merge($validatedData, [
            'file_path' => '',
            'file_name' => $file->getClientOriginalName(),
            'uploaded_at' => now(),
            'status' => DocumentStatus::PENDING,
            'metadata' => $this->extractFileMetadata($file),
        ]);

        $document = $this->repository->create($documentData);

        $filename = $this->buildDocumentFileName(
            $validatedData['type'],
            $validatedData['student_id'],
            $file->getClientOriginalExtension()
        );

        $media = $document->addMedia($file)
            ->usingFileName($filename)
            ->usingName($validatedData['type'])
            ->toMediaCollection($validatedData['type']);

        $document = $this->repository->update($document->id, [
            'media_id' => $media->id,
            'file_path' => $media->getPathRelativeToRoot(),
            'file_name' => $media->file_name,
        ]);

        // Notifications
        $this->notificationService->notifyUpload($document);

        // Log
        Log::info('Document uploaded successfully', [
            'document_id' => $document->id,
            'student_id' => $document->student_id,
            'type' => $document->type,
            'file_size' => $file->getSize(),
        ]);

        return $document;
    }

    /**
     * Approuver un document
     */
    public function approve(string $documentId, string $reviewedBy, ?string $notes = null): Document
    {
        $document = $this->repository->find($documentId);

        $this->validationService->validateReview($document, $reviewedBy);

        $updatedDocument = $this->repository->update($document->id, [
            'status' => DocumentStatus::APPROVED,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'notes' => $notes ? trim($document->notes . "\n" . $notes) : $document->notes,
        ]);

        $this->notificationService->notifyApproval($updatedDocument);

        Log::info('Document approved', [
            'document_id' => $documentId,
            'reviewed_by' => $reviewedBy,
        ]);

        return $updatedDocument;
    }

    /**
     * Rejeter un document
     */
    public function reject(string $documentId, string $reviewedBy, string $reason): Document
    {
        $document = $this->repository->find($documentId);

        $this->validationService->validateReview($document, $reviewedBy);

        $notes = trim($document->notes . "\n\nRejeté le " . now()->format('d/m/Y') .
            " par " . $reviewedBy . "\nRaison: " . $reason);

        $updatedDocument = $this->repository->update($document->id, [
            'status' => DocumentStatus::REJECTED,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'notes' => $notes,
        ]);

        $this->notificationService->notifyRejection($updatedDocument, $reason);

        Log::warning('Document rejected', [
            'document_id' => $documentId,
            'reviewed_by' => $reviewedBy,
            'reason' => $reason,
        ]);

        return $updatedDocument;
    }

    /**
     * Télécharger un document
     */
    /** @return array<string, mixed> */
    public function download(string $documentId): array
    {
        $document = $this->repository->find($documentId);

        $media = null;
        if ($document->media_id) {
            $media = $document->media()->whereKey($document->media_id)->first();
        }

        if ($media instanceof Media) {
            $temporaryUrl = null;
            $content = null;

            try {
                $temporaryUrl = $media->getTemporaryUrl(now()->addMinutes(30));
            } catch (\Throwable) {
                $temporaryUrl = null;
            }

            if ($temporaryUrl === null) {
                $content = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
            }

            return [
                'path' => $media->getPathRelativeToRoot(),
                'content' => $content,
                'temporary_url' => $temporaryUrl,
                'mime_type' => $media->mime_type,
                'original_name' => $media->file_name,
                'size' => $media->size,
                'disk' => $media->disk,
            ];
        }

        if (!$this->storageService->exists($document->file_path)) {
            throw new \RuntimeException('Le fichier n\'existe plus sur le serveur');
        }

        return [
            'path' => $document->file_path,
            'content' => $this->storageService->get($document->file_path),
            'temporary_url' => null,
            'mime_type' => $this->storageService->mimeType($document->file_path),
            'original_name' => $document->file_name,
            'size' => $this->storageService->size($document->file_path),
            'disk' => null,
        ];
    }

    /**
     * Supprimer un document
     */
    public function delete(string $documentId, string $deletedBy): bool
    {
        $document = $this->repository->find($documentId);

        if ($document->media_id) {
            $media = $document->media()->whereKey($document->media_id)->first();
            if ($media instanceof Media) {
                $media->delete();
            }
        } else {
            $this->storageService->delete($document->file_path);
        }

        // Supprimer de la base
        $this->repository->delete($document->id);

        Log::info('Document deleted', [
            'document_id' => $documentId,
            'student_id' => $document->student_id,
            'deleted_by' => $deletedBy,
            'type' => $document->type,
        ]);

        return true;
    }

    /**
     * Récupérer les documents d'un étudiant avec filtres
     */
    /** @param array<string, mixed> $filters */
    public function getStudentDocuments(string $studentId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Document::where('student_id', $studentId);

        // Appliquer les filtres
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('uploaded_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('uploaded_at', '<=', $filters['date_to']);
        }

        return $query->latest('uploaded_at')->paginate($perPage);
    }

    /**
     * Récupérer les documents en attente de review
     */
    /** @param array<string, mixed> $filters */
    public function getPendingDocuments(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Document::where('status', DocumentStatus::PENDING);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('uploaded_at', '>=', $filters['date_from']);
        }

        return $query->latest('uploaded_at')->paginate($perPage);
    }

    /**
     * Vérifier l'état des documents d'un étudiant
     */
    /** @return array<string, mixed> */
    public function checkStudentDocumentStatus(string $studentId): array
    {
        $allDocuments = Document::where('student_id', $studentId)->get();

        $status = [
            'total' => $allDocuments->count(),
            'pending' => $allDocuments->where('status', DocumentStatus::PENDING)->count(),
            'approved' => $allDocuments->where('status', DocumentStatus::APPROVED)->count(),
            'rejected' => $allDocuments->where('status', DocumentStatus::REJECTED)->count(),
            'by_type' => [],
            'required_docs_status' => [],
        ];

        // Statut par type
        foreach (DocumentType::cases() as $type) {
            $typeDocuments = $allDocuments->where('type', $type->value);
            $status['by_type'][$type->value] = [
                'total' => $typeDocuments->count(),
                'pending' => $typeDocuments->where('status', DocumentStatus::PENDING)->count(),
                'approved' => $typeDocuments->where('status', DocumentStatus::APPROVED)->count(),
                'rejected' => $typeDocuments->where('status', DocumentStatus::REJECTED)->count(),
                'latest' => $typeDocuments->sortByDesc('uploaded_at')->first(),
            ];
        }

        // Vérifier les documents requis
        $requiredTypes = config('documents.required_types', [
            DocumentType::CNI->value,
            DocumentType::BIRTH_CERT->value,
            DocumentType::BAC_DIPLOMA->value,
            DocumentType::PHOTO->value,
        ]);

        foreach ($requiredTypes as $requiredType) {
            $hasApproved = $allDocuments
                ->where('type', $requiredType)
                ->where('status', DocumentStatus::APPROVED)
                ->isNotEmpty();

            $status['required_docs_status'][$requiredType] = $hasApproved;
        }

        $status['all_required_approved'] = !in_array(false, $status['required_docs_status'], true);

        return $status;
    }

    /**
     * Générer un rapport
     */
    /** @param array<string, mixed> $filters */
    public function generateReport(array $filters = []): array
    {
        $query = Document::query();

        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('uploaded_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('uploaded_at', '<=', $filters['date_to']);
        }

        $documents = $query->get();

        return [
            'period' => [
                'from' => $filters['date_from'] ?? $documents->min('uploaded_at'),
                'to' => $filters['date_to'] ?? $documents->max('uploaded_at'),
            ],
            'summary' => [
                'total' => $documents->count(),
                'by_type' => $documents->groupBy('type')->map->count(),
                'by_status' => $documents->groupBy('status')->map->count(),
                'pending_count' => $documents->where('status', DocumentStatus::PENDING)->count(),
                'average_review_time' => $this->calculateAverageReviewTime($documents),
            ],
            'details' => [
                'pending_by_type' => $documents->where('status', DocumentStatus::PENDING)
                    ->groupBy('type')->map->count(),
                'rejection_rate' => $this->calculateRejectionRate($documents),
                'top_reviewers' => $this->getTopReviewers($documents),
            ],
        ];
    }

    /**
     * Mettre à jour les métadonnées d'un document
     */
    /** @param array<string, mixed> $metadata */
    public function updateMetadata(string $documentId, array $metadata): Document
    {
        $document = $this->repository->find($documentId);

        $currentMetadata = $document->metadata ?? [];
        $updatedMetadata = array_merge($currentMetadata, $metadata);

        return $this->repository->update($document->id, [
            'metadata' => $updatedMetadata,
        ]);
    }

    /**
     * Extraire les métadonnées du fichier
     */
    /** @return array<string, mixed> */
    private function extractFileMetadata(UploadedFile $file): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
            'upload_timestamp' => now()->timestamp,
            'checksum' => md5_file($file->getPathname()),
        ];

        // Métadonnées supplémentaires pour les images
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $imageInfo = @getimagesize($file->getPathname());
            if ($imageInfo) {
                $metadata['image'] = [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                    'type' => $imageInfo[2],
                    'bits' => $imageInfo['bits'] ?? null,
                    'channels' => $imageInfo['channels'] ?? null,
                ];
            }
        }

        // Métadonnées pour les PDF
        if ($file->getMimeType() === 'application/pdf') {
            $metadata['pdf'] = [
                'pages' => null, // Pourrait être extrait avec une bibliothèque PDF
                'version' => null,
            ];
        }

        return $metadata;
    }

    private function buildDocumentFileName(string $type, string $studentId, ?string $extension): string
    {
        $timestamp = now()->format('Ymd_His');
        $suffix = $extension ? strtolower($extension) : 'bin';
        $safeType = preg_replace('/[^A-Z0-9_]/', '', strtoupper($type));
        $safeStudent = preg_replace('/[^a-zA-Z0-9-]/', '', $studentId);

        return "{$safeType}_{$safeStudent}_{$timestamp}.{$suffix}";
    }

    /**
     * Calculer le temps moyen de review
     */
    /**
     * @param \Illuminate\Support\Collection<int, Document> $documents
     */
    private function calculateAverageReviewTime($documents): ?float
    {
        $reviewedDocs = $documents->whereNotNull('reviewed_at')->whereNotNull('uploaded_at');

        if ($reviewedDocs->isEmpty()) {
            return null;
        }

        $totalSeconds = $reviewedDocs->sum(function ($doc) {
            return $doc->reviewed_at->diffInSeconds($doc->uploaded_at);
        });

        return round($totalSeconds / $reviewedDocs->count() / 3600, 2); // En heures
    }

    /**
     * Calculer le taux de rejet
     */
    /**
     * @param \Illuminate\Support\Collection<int, Document> $documents
     */
    private function calculateRejectionRate($documents): float
    {
        $reviewed = $documents->whereNotNull('reviewed_at')->count();

        if ($reviewed === 0) {
            return 0.0;
        }

        $rejected = $documents->where('status', DocumentStatus::REJECTED)->count();

        return round(($rejected / $reviewed) * 100, 2);
    }

    /**
     * Obtenir les principaux reviewers
     */
    /**
     * @param \Illuminate\Support\Collection<int, Document> $documents
     * @return array<string, mixed>
     */
    private function getTopReviewers($documents): array
    {
        return $documents->whereNotNull('reviewed_by')
            ->groupBy('reviewed_by')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'approved' => $group->where('status', DocumentStatus::APPROVED)->count(),
                    'rejected' => $group->where('status', DocumentStatus::REJECTED)->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->toArray();
    }
}

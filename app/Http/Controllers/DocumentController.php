<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Requests\Document\ReviewDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\DocumentCollection;
use App\Services\Documents\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class DocumentController extends BaseApiController
{
    public function __construct(
        private readonly DocumentService $documentService
    ) {
        $this->middleware('permission:documents.view')->only([
            'index',
            'show',
            'download',
            'downloadFile',
            'studentDocuments',
            'pending',
            'report',
            'checkStatus',
        ]);
        $this->middleware('permission:documents.create')->only('store');
        $this->middleware('permission:documents.update')->only('update');
        $this->middleware('permission:documents.delete')->only('destroy');
        $this->middleware('permission:documents.review')->only(['approve', 'reject']);
        $this->middleware('permission:documents.download')->only(['download', 'downloadFile']);
    }

    /**
     * Liste des documents avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) ($request->integer('per_page') ?: 15);
        $filters = $request->only(['type', 'status', 'student_id', 'date_from', 'date_to']);

        // Si un student_id est spécifié, on utilise getStudentDocuments
        if (!empty($filters['student_id'])) {
            $documents = $this->documentService->getStudentDocuments(
                $filters['student_id'],
                $filters,
                $perPage
            );
        }
        // Si le statut est PENDING, on utilise getPendingDocuments
        elseif (!empty($filters['status']) && $filters['status'] === 'PENDING') {
            $documents = $this->documentService->getPendingDocuments($filters, $perPage);
        }
        // Sinon, on filtre directement
        else {
            $documents = \App\Models\Document::query();

            if (!empty($filters['type'])) {
                $documents->where('type', $filters['type']);
            }

            if (!empty($filters['status'])) {
                $documents->where('status', $filters['status']);
            }

            if (!empty($filters['date_from'])) {
                $documents->whereDate('uploaded_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $documents->whereDate('uploaded_at', '<=', $filters['date_to']);
            }

            $documents = $documents->latest('uploaded_at')->paginate($perPage);
        }

        return response()->json([
            'success' => true,
            'data' => DocumentResource::collection(collect($documents->items()))->resolve(),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
            'message' => 'Documents récupérés avec succès',
        ]);
    }

    /**
     * Upload d'un nouveau document
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        // Valider qu'un fichier est présent
        if (!$request->hasFile('document_file')) {
            return $this->error('Un fichier est requis pour l\'upload', 422);
        }

        $file = $request->file('document_file');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->error('Le fichier n\'est pas valide', 422);
        }

        try {
            $document = $this->documentService->upload(
                $request->validated(),
                $file
            );

            return $this->success(
                new DocumentResource($document),
                'Document uploadé avec succès',
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error(
                'Erreur de validation',
                422,
                $e->errors()
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Affichage d'un document spécifique
     */
    public function show(string $documentId): JsonResponse
    {
        try {
            $document = \App\Models\Document::findOrFail($documentId);

            return $this->success(
                new DocumentResource($document),
                'Document récupéré avec succès'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Document non trouvé', 404);
        }
    }

    /**
     * Mise à jour des métadonnées d'un document
     */
    public function update(UpdateDocumentRequest $request, string $documentId): JsonResponse
    {
        try {
            // Récupérer le document
            $document = \App\Models\Document::findOrFail($documentId);

            if ($document->status === \App\Enums\DocumentStatus::APPROVED) {
                return $this->error('Document approuvé, modification interdite.', 403);
            }

            // Mettre à jour uniquement les champs autorisés
            $validated = $request->validated();

            // Si des métadonnées sont fournies, les fusionner
            if (isset($validated['metadata'])) {
                $document = $this->documentService->updateMetadata(
                    $documentId,
                    $validated['metadata']
                );
                unset($validated['metadata']);
            }

            // Mettre à jour les autres champs
            $document->update($validated);

            return $this->success(
                new DocumentResource($document->fresh()),
                'Document mis à jour avec succès'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Document non trouvé', 404);
        }
    }

    /**
     * Approuver un document
     */
    public function approve(ReviewDocumentRequest $request, string $documentId): JsonResponse
    {
        try {
            $admin = $request->user()?->getOrCreateAdmin();

            $document = $this->documentService->approve(
                $documentId,
                $admin?->id ?? '',
                $request->input('notes')
            );

            return $this->success(
                new DocumentResource($document),
                'Document approuvé avec succès'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Document non trouvé', 404);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Rejeter un document
     */
    public function reject(ReviewDocumentRequest $request, string $documentId): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        try {
            $admin = $request->user()?->getOrCreateAdmin();

            $document = $this->documentService->reject(
                $documentId,
                $admin?->id ?? '',
                $request->input('reason')
            );

            return $this->success(
                new DocumentResource($document),
                'Document rejeté avec succès'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Document non trouvé', 404);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Télécharger un document
     */
    public function download(string $documentId): JsonResponse
    {
        try {
            $fileInfo = $this->documentService->download($documentId);

            // Retourner les informations pour le téléchargement
            return $this->success([
                'download_url' => route('documents.download.file', ['document' => $documentId]),
                'file_name' => $fileInfo['original_name'],
                'file_size' => $fileInfo['size'],
                'mime_type' => $fileInfo['mime_type'],
                'expires_in' => '5 minutes', // Si vous utilisez des URLs signées
            ], 'Prêt pour téléchargement');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Document non trouvé', 404);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * Endpoint pour le téléchargement physique du fichier
     */
    public function downloadFile(string $documentId)
    {
        try {
            $fileInfo = $this->documentService->download($documentId);

            return response()->streamDownload(
                function () use ($fileInfo) {
                    echo $fileInfo['content'];
                },
                $fileInfo['original_name'],
                [
                    'Content-Type' => $fileInfo['mime_type'],
                    'Content-Length' => strlen($fileInfo['content']),
                    'Content-Disposition' => 'attachment; filename="' . $fileInfo['original_name'] . '"',
                ]
            );
        } catch (\Exception $e) {
            abort(404, 'Fichier non trouvé');
        }
    }

    /**
     * Supprimer un document
     */
    public function destroy(string $documentId): JsonResponse
    {
        try {
            $this->documentService->delete(
                $documentId,
                request()->user()->id // Ou l'ID de la personne qui supprime
            );

            return $this->success(
                null,
                'Document supprimé avec succès',
                204
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Document non trouvé', 404);
        }
    }

    /**
     * Vérifier l'état des documents d'un étudiant
     */
    public function checkStatus(Request $request, ?string $studentId = null): JsonResponse
    {
        $studentId = $studentId ?: $request->input('student_id');

        if (!$studentId) {
            return $this->error('L\'identifiant de l\'étudiant est requis', 422);
        }

        try {
            $status = $this->documentService->checkStudentDocumentStatus($studentId);

            return $this->success(
                $status,
                'Statut des documents récupéré avec succès'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Erreur lors de la récupération du statut',
                500
            );
        }
    }

    /**
     * Générer un rapport
     */
    public function report(Request $request): JsonResponse
    {
        $filters = $request->only(['student_id', 'type', 'status', 'date_from', 'date_to']);

        try {
            $report = $this->documentService->generateReport($filters);

            return $this->success(
                $report,
                'Rapport généré avec succès'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Erreur lors de la génération du rapport',
                500
            );
        }
    }

    /**
     * Documents en attente de review
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = (int) ($request->integer('per_page') ?: 15);
        $filters = $request->only(['type', 'date_from']);

        $documents = $this->documentService->getPendingDocuments($filters, $perPage);

        return $this->success(
            DocumentResource::collection(collect($documents->items()))->resolve(),
            'Documents en attente récupérés avec succès'
        );
    }

    /**
     * Documents d'un étudiant spécifique
     */
    public function studentDocuments(Request $request, string $studentId): JsonResponse
    {
        $perPage = (int) ($request->integer('per_page') ?: 15);
        $filters = $request->only(['type', 'status', 'date_from', 'date_to']);

        $documents = $this->documentService->getStudentDocuments(
            $studentId,
            $filters,
            $perPage
        );

        return $this->success(
            DocumentResource::collection(collect($documents->items()))->resolve(),
            'Documents de l\'étudiant récupérés avec succès'
        );
    }
}

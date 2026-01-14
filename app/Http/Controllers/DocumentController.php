<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Requests\Document\ReviewDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\Student;
use App\Services\Documents\DocumentService;
use App\Enums\DocumentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

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
        $documents = QueryBuilder::for(Document::query())
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('student_id'),
                AllowedFilter::scope('uploaded_between'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['uploaded_at', 'created_at', 'status'])
            ->defaultSort('-uploaded_at')
            ->paginate($perPage)
            ->appends($request->query());

        return $this->success(
            DocumentResource::collection($documents),
            'Documents récupérés avec succès'
        );
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
            $document = DB::transaction(function () use ($request, $file) {
                return $this->documentService->upload(
                    $request->validated(),
                    $file
                );
            });

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
    public function show(Document $document): JsonResponse
    {
        return $this->success(
            new DocumentResource($document),
            'Document récupéré avec succès'
        );
    }

    /**
     * Mise à jour des métadonnées d'un document
     */
    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        if ($document->status === DocumentStatus::APPROVED) {
            return $this->error('Document approuvé, modification interdite.', 403);
        }

        // Mettre à jour uniquement les champs autorisés
        $validated = $request->validated();

        // Si des métadonnées sont fournies, les fusionner
        if (isset($validated['metadata'])) {
            $document = $this->documentService->updateMetadata(
                $document->id,
                $validated['metadata']
            );
            unset($validated['metadata']);
        }

        DB::transaction(function () use ($document, $validated) {
            $document->update($validated);
        });

        return $this->success(
            new DocumentResource($document->fresh()),
            'Document mis à jour avec succès'
        );
    }

    /**
     * Approuver un document
     */
    public function approve(ReviewDocumentRequest $request, Document $document): JsonResponse
    {
        try {
            $admin = $request->user()?->getOrCreateAdmin();

            $document = DB::transaction(function () use ($document, $admin, $request) {
                return $this->documentService->approve(
                    $document->id,
                    $admin?->id ?? '',
                    $request->input('notes')
                );
            });

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
    public function reject(ReviewDocumentRequest $request, Document $document): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        try {
            $admin = $request->user()?->getOrCreateAdmin();

            $document = DB::transaction(function () use ($document, $admin, $request) {
                return $this->documentService->reject(
                    $document->id,
                    $admin?->id ?? '',
                    $request->input('reason')
                );
            });

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
    public function download(Document $document): JsonResponse
    {
        try {
            $fileInfo = $this->documentService->download($document->id);

            // Retourner les informations pour le téléchargement
            return $this->success([
                'download_url' => route('documents.download.file', ['document' => $document->id]),
                'temporary_url' => $fileInfo['temporary_url'] ?? null,
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
    public function downloadFile(Document $document)
    {
        try {
            $fileInfo = $this->documentService->download($document->id);

            if (!empty($fileInfo['temporary_url'])) {
                return redirect()->away($fileInfo['temporary_url']);
            }

            return response()->streamDownload(
                function () use ($fileInfo) {
                    echo $fileInfo['content'] ?? '';
                },
                $fileInfo['original_name'],
                [
                    'Content-Type' => $fileInfo['mime_type'],
                    'Content-Length' => isset($fileInfo['content']) ? strlen($fileInfo['content']) : 0,
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
    public function destroy(Document $document): JsonResponse
    {
        try {
            $userId = request()->user()->id;
            DB::transaction(function () use ($document, $userId) {
                $this->documentService->delete(
                    $document->id,
                    $userId
                );
            });

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
    public function checkStatus(Request $request, ?Student $student = null): JsonResponse
    {
        $studentId = $student?->id ?: $request->input('student_id');

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
        $documents = QueryBuilder::for(Document::query())
            ->where('status', DocumentStatus::PENDING)
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::scope('uploaded_between'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['uploaded_at'])
            ->defaultSort('-uploaded_at')
            ->paginate($perPage)
            ->appends($request->query());

        return $this->success(
            DocumentResource::collection($documents),
            'Documents en attente récupérés avec succès'
        );
    }

    /**
     * Documents d'un étudiant spécifique
     */
    public function studentDocuments(Request $request, Student $student): JsonResponse
    {
        $perPage = (int) ($request->integer('per_page') ?: 15);
        $documents = QueryBuilder::for(Document::query())
            ->where('student_id', $student->id)
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('status'),
                AllowedFilter::scope('uploaded_between'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['uploaded_at', 'created_at'])
            ->defaultSort('-uploaded_at')
            ->paginate($perPage)
            ->appends($request->query());

        return $this->success(
            DocumentResource::collection($documents),
            'Documents de l\'étudiant récupérés avec succès'
        );
    }
}

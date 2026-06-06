<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GeneratedDocument\StoreGeneratedDocumentRequest;
use App\Http\Requests\GeneratedDocument\UpdateGeneratedDocumentRequest;
use App\Http\Resources\GeneratedDocumentResource;
use App\Jobs\BulkDocumentGenerationJob;
use App\Models\GeneratedDocument;
use App\Services\Documents\DocumentGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class GeneratedDocumentController extends BaseApiController
{
    public function __construct(
        private readonly DocumentGenerationService $generationService
    ) {
        $this->middleware('permission:generated_documents.view|documents.view')->only(['index', 'show', 'download']);
        $this->middleware('permission:generated_documents.create|documents.create')->only(['store', 'generate', 'bulkGenerate']);
        $this->middleware('permission:generated_documents.update|documents.update')->only(['update', 'issue', 'revoke']);
        $this->middleware('permission:generated_documents.delete|documents.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $generatedDocuments = QueryBuilder::for(GeneratedDocument::query())
            ->with(['student', 'generator'])
            ->allowedIncludes(['student', 'generator'])
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('student_id'),
                AllowedFilter::exact('generated_by'),
                AllowedFilter::scope('generated_between'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['generated_at', 'issued_at', 'created_at'])
            ->defaultSort('-generated_at')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success($generatedDocuments);
    }

    public function store(StoreGeneratedDocumentRequest $request): JsonResponse
    {
        $generatedDocument = DB::transaction(fn () => GeneratedDocument::create($request->validated()));
        Log::info('GeneratedDocument created', $generatedDocument->toArray());

        return $this->success(new GeneratedDocumentResource($generatedDocument->load(['student', 'generator'])), 'Generated document', Response::HTTP_CREATED);
    }

    public function show(GeneratedDocument $generatedDocument)
    {
        $this->authorize('view', $generatedDocument);

        return $this->success(new GeneratedDocumentResource($generatedDocument->load(['student', 'generator'])));
    }

    public function update(UpdateGeneratedDocumentRequest $request, GeneratedDocument $generatedDocument): JsonResponse
    {
        $this->authorize('update', $generatedDocument);

        DB::transaction(fn () => $generatedDocument->update($request->validated()));

        return $this->success(new GeneratedDocumentResource($generatedDocument->refresh()->load(['student', 'generator'])));
    }

    public function destroy(GeneratedDocument $generatedDocument): JsonResponse
    {
        $this->authorize('delete', $generatedDocument);

        DB::transaction(fn () => $generatedDocument->delete());

        return $this->success();
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'type' => ['required', 'string', 'in:'.implode(',', GeneratedDocument::getTypes())],
            'metadata' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:'.implode(',', GeneratedDocument::getStatuses())],
            'document_number' => ['nullable', 'string', 'max:255', 'unique:generated_documents,document_number'],
        ]);

        try {
            $generatedDocument = DB::transaction(function () use ($validated, $request) {
                return $this->generationService->generateDocument(
                    $validated,
                    (string) $request->user()->id
                );
            });

            return $this->success(
                new GeneratedDocumentResource($generatedDocument->load(['student', 'generator'])),
                'Document generated successfully',
                Response::HTTP_CREATED
            );
        } catch (\Throwable $e) {
            Log::error('Generated document failed', ['error' => $e->getMessage()]);

            return $this->error('La génération du document a échoué: '.$e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function issue(GeneratedDocument $generatedDocument, Request $request): JsonResponse
    {
        $this->authorize('update', $generatedDocument);

        try {
            $issued = DB::transaction(function () use ($generatedDocument, $request) {
                return $this->generationService->issueDocument($generatedDocument->id, (string) $request->user()->id);
            });

            return $this->success(
                new GeneratedDocumentResource($issued->load(['student', 'generator'])),
                'Document issued successfully'
            );
        } catch (\Throwable $e) {
            return $this->error('Impossible d\'émettre le document: '.$e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function revoke(GeneratedDocument $generatedDocument, Request $request): JsonResponse
    {
        $this->authorize('update', $generatedDocument);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $revoked = DB::transaction(function () use ($generatedDocument, $request, $validated) {
                return $this->generationService->revokeDocument(
                    $generatedDocument->id,
                    (string) $request->user()->id,
                    $validated['reason']
                );
            });

            return $this->success(
                new GeneratedDocumentResource($revoked->load(['student', 'generator'])),
                'Document revoked successfully'
            );
        } catch (\Throwable $e) {
            return $this->error('Impossible de révoquer le document: '.$e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function download(GeneratedDocument $generatedDocument)
    {
        $this->authorize('view', $generatedDocument);

        return $this->generationService->downloadDocument($generatedDocument);
    }

    public function verify(string $documentNumber): JsonResponse
    {
        $document = GeneratedDocument::query()
            ->with(['student', 'generator'])
            ->where('document_number', $documentNumber)
            ->first();

        if (! $document) {
            return $this->error('Document not found', Response::HTTP_NOT_FOUND);
        }

        $payload = (new GeneratedDocumentResource($document))->resolve();
        $payload['is_valid'] = $document->status !== GeneratedDocument::STATUS_REVOKED;

        return $this->success($payload, 'Document verified successfully');
    }

    public function bulkGenerate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', GeneratedDocument::getTypes())],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['uuid', 'exists:students,id'],
            'metadata' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:'.implode(',', GeneratedDocument::getStatuses())],
        ]);

        BulkDocumentGenerationJob::dispatch(
            $validated['type'],
            $validated['student_ids'],
            $validated['metadata'] ?? [],
            (string) $request->user()->id,
            $validated['status'] ?? GeneratedDocument::STATUS_DRAFT
        );

        return $this->success([
            'student_count' => count($validated['student_ids']),
            'status' => 'queued',
        ], 'Bulk generation queued successfully', Response::HTTP_ACCEPTED);
    }
}

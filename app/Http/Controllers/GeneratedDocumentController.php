<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use App\Http\Requests\GeneratedDocument\StoreGeneratedDocumentRequest;
use App\Http\Requests\GeneratedDocument\UpdateGeneratedDocumentRequest;
use App\Http\Resources\GeneratedDocumentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class GeneratedDocumentController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('permission:generated_documents.view')->only(['index', 'show']);
        $this->middleware('permission:generated_documents.create')->only('store');
        $this->middleware('permission:generated_documents.update')->only('update');
        $this->middleware('permission:generated_documents.delete')->only('destroy');
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
            ])
            ->allowedSorts(['generated_at', 'issued_at', 'created_at'])
            ->defaultSort('-generated_at')
            ->paginate($request->integer('per_page') ?? 15)
            ->appends($request->query());

        return $this->success($generatedDocuments);
    }

    public function store(StoreGeneratedDocumentRequest $request): JsonResponse
    {
        $generatedDocument = DB::transaction(fn() => GeneratedDocument::create($request->validated()));
        Log::info('GeneratedDocument created', $generatedDocument->toArray());
        return $this->success($generatedDocument->load(['student', 'generator']), 'Generated document', Response::HTTP_CREATED);
    }

    public function show(GeneratedDocument $generatedDocument)
    {
        return $this->success($generatedDocument->load(['student', 'generator']));
    }

    public function update(UpdateGeneratedDocumentRequest $request, GeneratedDocument $generatedDocument): JsonResponse
    {
        DB::transaction(fn() => $generatedDocument->update($request->validated()));
        return $this->success($generatedDocument->refresh()->load(['student', 'generator']));
    }

    public function destroy(GeneratedDocument $generatedDocument): JsonResponse
    {
        DB::transaction(fn() => $generatedDocument->delete());
        return $this->success();
    }

    public function verify(string $documentNumber)
    {
        $document = GeneratedDocument::where('document_number', $documentNumber)->firstOrFail();
        return $this->success($document, 'Document verified successfully');
    }
}

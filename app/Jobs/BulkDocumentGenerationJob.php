<?php

namespace App\Jobs;

use App\Models\GeneratedDocument;
use App\Services\Documents\DocumentGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BulkDocumentGenerationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $studentIds
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        private readonly string $type,
        private readonly array $studentIds,
        private readonly array $metadata,
        private readonly string $generatedBy,
        private readonly string $status = GeneratedDocument::STATUS_DRAFT
    ) {}

    public function handle(DocumentGenerationService $generationService): void
    {
        foreach ($this->studentIds as $studentId) {
            try {
                $generationService->generateDocument([
                    'type' => $this->type,
                    'student_id' => $studentId,
                    'metadata' => $this->metadata,
                    'status' => $this->status,
                ], $this->generatedBy);
            } catch (\Throwable $e) {
                Log::warning('Bulk document generation failed for student', [
                    'student_id' => $studentId,
                    'type' => $this->type,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

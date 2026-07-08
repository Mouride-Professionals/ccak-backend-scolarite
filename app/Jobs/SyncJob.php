<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\SyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * No retries — sync is long-running and idempotent; a second attempt
     * would just re-run the full sync, which should be triggered explicitly.
     */
    public int $tries = 1;

    /**
     * 10 minutes — enough for paginating all CCAK entities.
     */
    public int $timeout = 600;

    public function __construct(private readonly ?string $entityType = null) {}

    public function handle(SyncService $syncService): void
    {
        Log::info('SyncJob started', ['entity_type' => $this->entityType ?? 'all']);

        if ($this->entityType) {
            match ($this->entityType) {
                'degree_cycles' => $syncService->syncDegreeCycles(),
                'niveaux' => $syncService->syncNiveaux(),
                'academic_years' => $syncService->syncAcademicYears(),
                'ufr' => $syncService->syncUfr(),
                'departements' => $syncService->syncDepartements(),
                'programmes' => $syncService->syncProgrammes(),
                'students' => $syncService->syncStudents(),
                'enrollments' => $syncService->syncEnrollments(),
                default => throw new \InvalidArgumentException("Entité inconnue : {$this->entityType}"),
            };
        } else {
            $syncService->syncAll();
        }

        Log::info('SyncJob completed', ['entity_type' => $this->entityType ?? 'all']);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncJob failed', [
            'entity_type' => $this->entityType ?? 'all',
            'error' => $exception->getMessage(),
        ]);
    }
}

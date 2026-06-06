<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\SemesterResultCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateSemesterResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 3600; // 1 hour

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $academicYearId,
        private readonly int $semester,
        private readonly string $calculatedByUserId,
        private readonly string $jobId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SemesterResultCalculationService $calculationService): void
    {
        Log::info('Starting semester results calculation job', [
            'job_id' => $this->jobId,
            'academic_year_id' => $this->academicYearId,
            'semester' => $this->semester,
            'calculated_by' => $this->calculatedByUserId,
        ]);

        try {
            $user = User::findOrFail($this->calculatedByUserId);

            $result = $calculationService->calculateSemesterResults(
                $this->academicYearId,
                $this->semester,
                $user
            );

            Log::info('Semester results calculation job completed', [
                'job_id' => $this->jobId,
                'success' => $result['success'],
                'students_processed' => $result['data']['students_processed'] ?? 0,
                'results_created' => $result['data']['results_created'] ?? 0,
                'errors' => count($result['data']['errors'] ?? []),
            ]);

            // TODO: Notify admin of completion
            // You could dispatch a notification job here
            // dispatch(new NotifyAdminOfCalculationCompletion($result, $user));

        } catch (\Exception $e) {
            Log::error('Semester results calculation job failed', [
                'job_id' => $this->jobId,
                'academic_year_id' => $this->academicYearId,
                'semester' => $this->semester,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Semester results calculation job failed permanently', [
            'job_id' => $this->jobId,
            'academic_year_id' => $this->academicYearId,
            'semester' => $this->semester,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // TODO: Notify admin of failure
        // You could send an email or create a notification here
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->jobId;
    }
}

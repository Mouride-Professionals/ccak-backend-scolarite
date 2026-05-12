<?php

namespace App\Console\Commands;

use App\Exceptions\CcakApiException;
use App\Services\SyncService;
use Illuminate\Console\Command;

class SyncCcakStudents extends Command
{
    protected $signature = 'sync:ccak-students';
    protected $description = 'Sync all referential data and students from the CCAK external API';

    public function __construct(private readonly SyncService $syncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting CCAK full sync...');

        try {
            $logs = $this->syncService->syncAll();

            foreach ($logs as $entity => $log) {
                $icon = match ($log->status->value) {
                    'SUCCESS' => '[OK]',
                    'PARTIAL' => '[PARTIAL]',
                    default   => '[FAILED]',
                };

                $this->line(sprintf(
                    '  %-16s %s  received=%-4d  updated=%-4d  errors=%d',
                    $entity,
                    $icon,
                    $log->total_received,
                    $log->total_updated,
                    $log->total_errors
                ));

                if ($log->total_errors > 0) {
                    $this->warn("    → Check sync_logs id={$log->id} for error details.");
                }
            }

            $anyFailed = collect($logs)->contains(fn ($l) => $l->status->value === 'FAILED');

            if ($anyFailed) {
                $this->error('Sync completed with failures.');
                return self::FAILURE;
            }

            $this->info('Sync completed successfully.');
            return self::SUCCESS;
        } catch (CcakApiException $e) {
            $this->error("CCAK API error: {$e->getMessage()}");
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Unexpected error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}

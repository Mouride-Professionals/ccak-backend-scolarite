<?php

namespace App\Console\Commands;

use App\Exceptions\CcakApiException;
use App\Services\SyncService;
use Illuminate\Console\Command;

class SyncCcakStudents extends Command
{
    protected $signature = 'sync:ccak-students
                            {--entity= : Sync only one entity (degree_cycles|niveaux|ufr|departements|programmes|academic_years|students|enrollments)}
                            {--dry : Fetch and map records without writing to the database}
                            {--limit= : Process only the first N records}';

    protected $description = 'Sync all referential data and students from the CCAK external API';

    public function __construct(private readonly SyncService $syncService)
    {
        parent::__construct();
    }

    private array $entityMap = [
        'degree_cycles'  => 'syncDegreeCycles',
        'niveaux'        => 'syncNiveaux',
        'ufr'            => 'syncUfr',
        'departements'   => 'syncDepartements',
        'programmes'     => 'syncProgrammes',
        'academic_years' => 'syncAcademicYears',
        'students'       => 'syncStudents',
        'enrollments'    => 'syncEnrollments',
    ];

    private function runDry(?string $entity): int
    {
        $target = $entity ?? 'students';

        if ($target !== 'students') {
            $this->warn("--dry is only supported for --entity=students. Received: '{$target}'");

            return self::FAILURE;
        }

        try {
            $raw = $this->syncService->fetchStudentsFromCcak();
            $limit = $this->option('limit') ? (int) $this->option('limit') : null;
            if ($limit !== null) {
                $raw = array_slice($raw, 0, $limit);
            }
            $this->info(sprintf('Fetched %d student(s) from CCAK (showing %d). No DB writes.', count($raw), count($raw)));
            $this->newLine();

            foreach ($raw as $i => $s) {
                $mapped = $this->syncService->mapCcakStudentToMp($s);

                $this->line(sprintf(
                    '  [%d] id=%-36s  name="%s %s"  status=%s',
                    $i + 1,
                    $mapped['id'],
                    $mapped['first_name'] ?? '?',
                    $mapped['last_name'] ?? '?',
                    $mapped['status'] ?? '?',
                ));
            }

            $this->newLine();
            $this->info('Dry run complete — no data was written.');

            return self::SUCCESS;
        } catch (\App\Exceptions\CcakApiException $e) {
            $this->error("CCAK API error: {$e->getMessage()}");

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Unexpected error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    public function handle(): int
    {
        $entity = $this->option('entity');

        if ($entity !== null && ! array_key_exists($entity, $this->entityMap)) {
            $this->error("Unknown entity '{$entity}'. Valid values: ".implode(', ', array_keys($this->entityMap)));

            return self::FAILURE;
        }

        $dry = $this->option('dry');
        $label = ($entity ? "'{$entity}'" : 'full').($dry ? ' [DRY RUN]' : '');
        $this->info("Starting CCAK {$label} sync...");

        if ($dry) {
            return $this->runDry($entity);
        }

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        try {
            if ($entity !== null) {
                $method = $this->entityMap[$entity];
                $logs = [$entity => $this->syncService->{$method}($limit)];
            } else {
                $logs = $this->syncService->syncAll();
            }

            foreach ($logs as $entity => $log) {
                $icon = match ($log->status->value) {
                    'SUCCESS' => '[OK]',
                    'PARTIAL' => '[PARTIAL]',
                    default => '[FAILED]',
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

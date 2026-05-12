<?php

namespace App\Services;

use App\Enums\AcademicYearStatus;
use App\Enums\SyncStatus;
use App\Exceptions\CcakApiException;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\DegreeCycle;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Level;
use App\Models\Student;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncService
{
    public function __construct(private readonly CcakApiClient $client) {}

    // -------------------------------------------------------------------------
    // Public sync methods
    // -------------------------------------------------------------------------

    public function syncDegreeCycles(): SyncLog
    {
        return $this->runSync('degree_cycles', fn () => $this->client->getGrades(), function (array $raw) {
            DegreeCycle::updateOrCreate(
                ['id' => $raw['id']],
                [
                    'name'           => $raw['name'],
                    'code'           => $raw['code'],
                    'type'           => $raw['type'],
                    'synced_from'    => 'CCAK',
                    'last_synced_at' => now(),
                ]
            );
        });
    }

    public function syncNiveaux(): SyncLog
    {
        return $this->runSync('niveaux', fn () => $this->client->getNiveaux(), function (array $raw) {
            Level::updateOrCreate(
                ['id' => $raw['id']],
                [
                    'name'            => $raw['name'],
                    'code'            => $raw['code'],
                    'degree_cycle_id' => $raw['gradeId'] ?? $raw['grade_id'] ?? $raw['degreeCycleId'] ?? $raw['degree_cycle_id'] ?? null,
                    'numero'          => $raw['numero'] ?? null,
                    'synced_from'     => 'CCAK',
                    'last_synced_at'  => now(),
                ]
            );
        });
    }

    public function syncUfr(): SyncLog
    {
        return $this->runSync('ufr', fn () => $this->client->getUfr(), function (array $raw) {
            Faculty::updateOrCreate(
                ['id' => $raw['id']],
                [
                    'name'           => $raw['name'],
                    'code'           => $raw['code'],
                    'synced_from'    => 'CCAK',
                    'last_synced_at' => now(),
                ]
            );
        });
    }

    public function syncDepartements(): SyncLog
    {
        return $this->runSync('departements', fn () => $this->client->getDepartements(), function (array $raw) {
            $facultyId = $raw['ufrId'] ?? $raw['ufr_id'] ?? $raw['facultyId'] ?? $raw['faculty_id'] ?? null;

            Department::withTrashed()->updateOrCreate(
                ['id' => $raw['id']],
                [
                    'faculty_id'     => $facultyId,
                    'name'           => $raw['name'],
                    'code'           => $raw['code'],
                    'synced_from'    => 'CCAK',
                    'last_synced_at' => now(),
                ]
            );
        });
    }

    public function syncProgrammes(): SyncLog
    {
        $validLevels = ['LICENCE', 'MASTER', 'DOCTORAT'];

        return $this->runSync('programmes', fn () => $this->client->getProgrammes(), function (array $raw) use ($validLevels) {
            $level = $raw['level'] ?? $raw['grade'] ?? null;

            if (! in_array($level, $validLevels, true)) {
                throw new \InvalidArgumentException("Unsupported programme level: '{$level}' (id={$raw['id']})");
            }

            $departmentId = $raw['departmentId'] ?? $raw['department_id'] ?? null;

            AcademicProgram::updateOrCreate(
                ['id' => $raw['id']],
                [
                    'department_id'          => $departmentId,
                    'name'                   => $raw['name'],
                    'level'                  => $level,
                    'duration_semesters'     => $raw['durationSemesters'] ?? $raw['duration_semesters'] ?? 0,
                    'total_credits_required' => $raw['totalCreditsRequired'] ?? $raw['total_credits_required'] ?? 0,
                    'synced_from'            => 'CCAK',
                    'last_synced_at'         => now(),
                ]
            );
        });
    }

    public function syncAcademicYears(): SyncLog
    {
        return $this->runSync('academic_years', fn () => $this->client->getAcademicYears(), function (array $raw) {
            $isCurrent = ($raw['status'] ?? '') === AcademicYearStatus::OPEN->value;

            if ($isCurrent) {
                DB::table('academic_years')->where('id', '!=', $raw['id'])->update(['is_current' => false]);
            }

            AcademicYear::updateOrCreate(
                ['id' => $raw['id']],
                [
                    'name'           => $raw['name'],
                    'code'           => $raw['code'] ?? null,
                    'status'         => $raw['status'] ?? null,
                    'is_current'     => $isCurrent,
                    'synced_from'    => 'CCAK',
                    'last_synced_at' => now(),
                ]
            );
        });
    }

    public function syncStudents(): SyncLog
    {
        return $this->runSync('students', fn () => $this->fetchStudentsFromCcak(), function (array $raw) {
            $this->upsertStudent($this->mapCcakStudentToMp($raw));
        });
    }

    /**
     * Run all syncs in dependency order.
     *
     * @return SyncLog[]
     */
    public function syncAll(): array
    {
        return [
            'degree_cycles'  => $this->syncDegreeCycles(),
            'niveaux'        => $this->syncNiveaux(),
            'ufr'            => $this->syncUfr(),
            'departements'   => $this->syncDepartements(),
            'programmes'     => $this->syncProgrammes(),
            'academic_years' => $this->syncAcademicYears(),
            'students'       => $this->syncStudents(),
        ];
    }

    // -------------------------------------------------------------------------
    // Student-specific helpers (preserved from original implementation)
    // -------------------------------------------------------------------------

    public function fetchStudentsFromCcak(): array
    {
        return $this->client->getStudentsBulk();
    }

    public function mapCcakStudentToMp(array $s): array
    {
        $firstName = $s['firstName'] ?? $s['first_name'] ?? null;
        $lastName  = $s['lastName']  ?? $s['last_name']  ?? null;
        $fullName  = $s['fullName']  ?? $s['full_name']  ?? trim("{$firstName} {$lastName}") ?: null;

        return [
            'id'                  => $s['id'],
            'first_name'          => $firstName,
            'last_name'           => $lastName,
            'full_name'           => $fullName,
            'ine'                 => $s['ine'] ?? null,
            'registration_number' => $s['registrationNumber'] ?? $s['registration_number'] ?? null,
            'status'              => $this->mapStudentStatus($s['status'] ?? ''),
            'level_id'            => $s['levelId'] ?? $s['level_id'] ?? null,
            'gender'              => $s['gender'] ?? null,
            'date_of_birth'       => $s['dateOfBirth'] ?? $s['date_of_birth'] ?? null,
            'place_of_birth'      => $s['placeOfBirth'] ?? $s['place_of_birth'] ?? null,
            'nationality'         => $s['nationality'] ?? null,
            'phone'               => $s['phone'] ?? null,
            'address'             => $s['address'] ?? null,
            'provenance'          => $s['provenance'] ?? null,
            'synced_from'         => 'CCAK',
            'last_synced_at'      => now(),
        ];
    }

    public function mapStudentStatus(string $ccakStatus): string
    {
        return match ($ccakStatus) {
            'Pending'   => 'PENDING',
            'Active'    => 'ACTIVE',
            'Suspended' => 'SUSPENDED',
            'Graduated' => 'GRADUATED',
            'Inactive'  => 'WITHDRAWN',
            default     => 'PENDING',
        };
    }

    public function upsertStudent(array $mpStudent): string
    {
        $existing = Student::find($mpStudent['id']);

        if ($existing) {
            $existing->update([
                'first_name'          => $mpStudent['first_name'],
                'last_name'           => $mpStudent['last_name'],
                'full_name'           => $mpStudent['full_name'],
                'ine'                 => $mpStudent['ine'],
                'registration_number' => $mpStudent['registration_number'],
                'status'              => $mpStudent['status'],
                'provenance'          => $mpStudent['provenance'],
                'last_synced_at'      => $mpStudent['last_synced_at'],
                'synced_from'         => $mpStudent['synced_from'],
            ]);

            return 'UPDATED';
        }

        Student::create($mpStudent);

        return 'CREATED';
    }

    // -------------------------------------------------------------------------
    // Private helper
    // -------------------------------------------------------------------------

    /**
     * Generic sync runner: creates a SyncLog, iterates records, updates log on completion.
     *
     * @param callable(): array $fetchFn    Returns the raw array from CCAK
     * @param callable(array): void $processFn  Processes a single record (throw to count as error)
     */
    private function runSync(string $entityType, callable $fetchFn, callable $processFn): SyncLog
    {
        $log = SyncLog::create([
            'batch_date'  => today(),
            'source'      => 'CCAK',
            'entity_type' => $entityType,
            'started_at'  => now(),
            'status'      => SyncStatus::PARTIAL->value,
        ]);

        $created = 0;
        $updated = 0;
        $errors  = 0;
        $total   = 0;
        $errorDetails = [];

        try {
            $items = $fetchFn();
            $total = count($items);

            foreach ($items as $raw) {
                try {
                    $processFn($raw);
                    // Distinguish created/updated via wasRecentlyCreated on the model if needed;
                    // for simplicity we count all successful ops as updated
                    $updated++;
                } catch (\Throwable $e) {
                    $errors++;
                    $errorDetails[] = [
                        'id'    => $raw['id'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                    Log::warning("SyncService[{$entityType}]: record failed", [
                        'error' => $e->getMessage(),
                        'id'    => $raw['id'] ?? null,
                    ]);
                }
            }

            $status = $errors === 0 ? SyncStatus::SUCCESS : SyncStatus::PARTIAL;
        } catch (CcakApiException $e) {
            $status = SyncStatus::FAILED;
            $errorDetails[] = ['error' => $e->getMessage()];
            Log::error("SyncService[{$entityType}]: API fetch failed", ['error' => $e->getMessage()]);
        }

        $log->update([
            'total_received' => $total,
            'total_created'  => $created,
            'total_updated'  => $updated,
            'total_errors'   => $errors,
            'error_details'  => $errorDetails ?: null,
            'completed_at'   => now(),
            'status'         => $status->value,
        ]);

        return $log->fresh();
    }
}

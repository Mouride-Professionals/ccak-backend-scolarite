<?php

namespace App\Services;

use App\Enums\AcademicYearStatus;
use App\Enums\AddressType;
use App\Enums\Provenance;
use App\Enums\StudentStatus;
use App\Enums\SyncStatus;
use App\Exceptions\CcakApiException;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Address;
use App\Models\DegreeCycle;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Guardian;
use App\Models\Level;
use App\Models\PriorDiploma;
use App\Models\SocialProfile;
use App\Models\Student;
use App\Models\StudentBacInfo;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncService
{
    private const NULL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __construct(private readonly CcakApiClient $client) {}

    // -------------------------------------------------------------------------
    // Public sync methods
    // -------------------------------------------------------------------------

    public function syncDegreeCycles(): SyncLog
    {
        return $this->runSync('degree_cycles', fn () => $this->client->getGrades(), function (array $raw) {
            $model = DegreeCycle::updateOrCreate(
                ['id' => $raw['id']],
                [
                    'name' => $raw['name'],
                    'code' => $raw['code'],
                    'type' => $raw['type'],
                    'synced_from' => 'CCAK',
                    'last_synced_at' => now(),
                ]
            );

            return $model->wasRecentlyCreated ? 'CREATED' : 'UPDATED';
        });
    }

    public function syncNiveaux(): SyncLog
    {
        return $this->runSync('niveaux', fn () => $this->client->getNiveaux(), function (array $raw) {
            $model = Level::updateOrCreate(
                ['id' => $raw['id']],
                [
                    'name' => $raw['name'],
                    'code' => $raw['code'],
                    'degree_cycle_id' => $raw['gradeId'] ?? $raw['grade_id'] ?? $raw['degreeCycleId'] ?? $raw['degree_cycle_id'] ?? null,
                    'numero' => $raw['numero'] ?? null,
                    'synced_from' => 'CCAK',
                    'last_synced_at' => now(),
                ]
            );

            return $model->wasRecentlyCreated ? 'CREATED' : 'UPDATED';
        });
    }

    public function syncUfr(): SyncLog
    {
        return $this->runSync('ufr', fn () => $this->client->getUfr(), function (array $raw) {
            $model = Faculty::updateOrCreate(
                ['id' => $raw['id']],
                [
                    'name' => $raw['name'],
                    'code' => $raw['code'],
                    'synced_from' => 'CCAK',
                    'last_synced_at' => now(),
                ]
            );

            return $model->wasRecentlyCreated ? 'CREATED' : 'UPDATED';
        });
    }

    public function syncDepartements(): SyncLog
    {
        return $this->runSync('departements', fn () => $this->client->getDepartements(), function (array $raw) {
            $facultyId = $raw['ufrId'] ?? $raw['ufr_id'] ?? $raw['facultyId'] ?? $raw['faculty_id'] ?? null;

            $model = Department::withTrashed()->updateOrCreate(
                ['id' => $raw['id']],
                [
                    'faculty_id' => $facultyId,
                    'name' => $raw['name'],
                    'code' => $raw['code'],
                    'synced_from' => 'CCAK',
                    'last_synced_at' => now(),
                ]
            );

            return $model->wasRecentlyCreated ? 'CREATED' : 'UPDATED';
        });
    }

    public function syncProgrammes(): SyncLog
    {
        return $this->runSync('programmes', fn () => $this->client->getProgrammes(), function (array $raw) {
            // CCAK programs endpoint does not expose a level field — infer from name.
            $name = $raw['name'] ?? '';
            $level = match (true) {
                (bool) preg_match('/master/i', $name) => 'MASTER',
                (bool) preg_match('/doctorat/i', $name) => 'DOCTORAT',
                (bool) preg_match('/pr[eé]paratoire/i', $name) => 'CLASSE_PREPARATOIRE',
                default => 'LICENCE',
            };

            $departmentId = $raw['departmentId'] ?? $raw['department_id'] ?? null;

            $model = AcademicProgram::updateOrCreate(
                ['id' => $raw['id']],
                [
                    'department_id' => $departmentId,
                    'name' => $raw['name'],
                    'level' => $level,
                    'duration_semesters' => $raw['durationSemesters'] ?? $raw['duration_semesters'] ?? 0,
                    'total_credits_required' => $raw['totalCreditsRequired'] ?? $raw['total_credits_required'] ?? 0,
                    'synced_from' => 'CCAK',
                    'last_synced_at' => now(),
                ]
            );

            return $model->wasRecentlyCreated ? 'CREATED' : 'UPDATED';
        });
    }

    public function syncAcademicYears(): SyncLog
    {
        return $this->runSync('academic_years', fn () => $this->client->getAcademicYears(), function (array $raw) {
            $isCurrent = ($raw['status'] ?? '') === AcademicYearStatus::OPEN->value;

            if ($isCurrent) {
                DB::table('academic_years')->where('id', '!=', $raw['id'])->update(['is_current' => false]);
            }

            $model = AcademicYear::updateOrCreate(
                ['id' => $raw['id']],
                [
                    'name' => $raw['name'],
                    'code' => $raw['code'] ?? null,
                    'status' => $raw['status'] ?? null,
                    'is_current' => $isCurrent,
                    'synced_from' => 'CCAK',
                    'last_synced_at' => now(),
                ]
            );

            return $model->wasRecentlyCreated ? 'CREATED' : 'UPDATED';
        });
    }

    public function syncStudents(?int $limit = null): SyncLog
    {
        return $this->runSync('students', function () use ($limit) {
            $items = $this->fetchStudentsFromCcak();

            return $limit !== null ? array_slice($items, 0, $limit) : $items;
        }, function (array $raw) {
            $studentId = $raw['id'];
            $result = $this->upsertStudent($this->mapCcakStudentToMp($raw));
            $this->upsertStudentBacInfo($studentId, $raw);
            $this->upsertSocialProfile($studentId, $raw);
            $this->upsertStudentAddresses($studentId, $raw);
            $this->upsertGuardian($studentId, $raw);

            return $result;
        });
    }

    public function syncEnrollments(?int $limit = null): SyncLog
    {
        return $this->runSync('enrollments', function () use ($limit) {
            $items = $this->client->getRegistrationsBulk();

            return $limit !== null ? array_slice($items, 0, $limit) : $items;
        }, function (array $raw) {
            return $this->upsertEnrollment($raw);
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
            // degree_cycles and niveaux: no CCAK endpoint yet — seeded statically.
            // Uncomment both together once the grades endpoint is available:
            // 'degree_cycles' => $this->syncDegreeCycles(),
            // 'niveaux'       => $this->syncNiveaux(),
            'academic_years' => $this->syncAcademicYears(),
            'ufr' => $this->syncUfr(),
            'departements' => $this->syncDepartements(), // depends on ufr
            'programmes' => $this->syncProgrammes(),   // depends on departements
            'students' => $this->syncStudents(),
            'enrollments' => $this->syncEnrollments(),  // depends on students + programmes + academic_years
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
        $lastName = $s['lastName'] ?? $s['last_name'] ?? null;
        $fullName = $s['fullName'] ?? $s['full_name'] ?? trim("{$firstName} {$lastName}") ?: null;

        return [
            'id' => $s['id'],
            'student_number' => $s['studentId'] ?? $s['student_id'] ?? $s['studentNumber'] ?? $s['student_number'] ?? null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $fullName,
            'ine' => $s['ine'] ?? null,
            'registration_number' => $s['registrationNumber'] ?? $s['registration_number'] ?? null,
            'status' => $this->mapStudentStatus($s['status'] ?? ''),
            'gender' => $s['gender'] ?? null,
            'date_of_birth' => $s['dateOfBirth'] ?? $s['date_of_birth'] ?? null,
            'place_of_birth' => $s['placeOfBirth'] ?? $s['place_of_birth'] ?? null,
            'nationality' => $s['nationality'] ?? null,
            'phone' => $s['phone'] ?? null,
            'phone_2' => $s['phone2'] ?? $s['phone_2'] ?? null,
            'address' => $s['address'] ?? null,
            'email' => $s['email'] ?? null,
            'email_university' => ($s['emailUniversity'] ?? $s['email_university'] ?? null) ?: null,
            'type_of_id' => $this->mapIdType($s['typeOfId'] ?? $s['type_of_id'] ?? null),
            'id_details' => $s['idDetails'] ?? $s['id_details'] ?? null,
            'photo_url' => $s['photo'] ?? $s['photo_url'] ?? null,
            'emergency_contact_name' => trim(($s['emergencyContactFirstName'] ?? '').' '.($s['emergencyContactLastName'] ?? '')) ?: null,
            'emergency_contact_phone' => $s['emergencyContactPhone1'] ?? $s['emergency_contact_phone'] ?? null,
            'provenance' => $this->mapProvenance($s['provenance'] ?? null),
            'synced_from' => 'CCAK',
            'last_synced_at' => now(),
        ];
    }

    public function mapProvenance(mixed $value): ?string
    {
        if (is_numeric($value)) {
            try {
                return CcakEnumMapper::provenance((int) $value)->value;
            } catch (\InvalidArgumentException) {
                return null;
            }
        }

        return match (strtoupper((string) $value)) {
            'ETAT' => Provenance::ETAT->value,
            'PLATEFORME' => Provenance::PLATEFORME->value,
            default => null,
        };
    }

    public function mapStudentStatus(mixed $ccakStatus): string
    {
        if (is_numeric($ccakStatus)) {
            try {
                return CcakEnumMapper::studentStatus((int) $ccakStatus)->value;
            } catch (\InvalidArgumentException) {
                return StudentStatus::PENDING->value;
            }
        }

        // CCAK may also send PascalCase string names
        return match ($ccakStatus) {
            'Pending' => StudentStatus::PENDING->value,
            'Active' => StudentStatus::ACTIVE->value,
            'Suspended' => StudentStatus::SUSPENDED->value,
            'Graduated' => StudentStatus::GRADUATED->value,
            'Inactive' => StudentStatus::INACTIVE->value,
            default => StudentStatus::PENDING->value,
        };
    }

    public function upsertStudent(array $mpStudent): string
    {
        $existing = Student::find($mpStudent['id']);

        if (! $existing && $mpStudent['student_number']) {
            $existing = Student::where('student_number', $mpStudent['student_number'])->first();
        }

        if ($existing) {
            $update = [
                'student_number' => $mpStudent['student_number'],
                'first_name' => $mpStudent['first_name'],
                'last_name' => $mpStudent['last_name'],
                'full_name' => $mpStudent['full_name'],
                'ine' => $mpStudent['ine'],
                'registration_number' => $mpStudent['registration_number'],
                'status' => $mpStudent['status'],
                'gender' => $mpStudent['gender'],
                'date_of_birth' => $mpStudent['date_of_birth'],
                'place_of_birth' => $mpStudent['place_of_birth'],
                'nationality' => $mpStudent['nationality'],
                'phone' => $mpStudent['phone'],
                'phone_2' => $mpStudent['phone_2'],
                'address' => $mpStudent['address'],
                'email' => $mpStudent['email'],
                'type_of_id' => $mpStudent['type_of_id'],
                'id_details' => $mpStudent['id_details'],
                'photo_url' => $mpStudent['photo_url'],
                'emergency_contact_name' => $mpStudent['emergency_contact_name'],
                'emergency_contact_phone' => $mpStudent['emergency_contact_phone'],
                'provenance' => $mpStudent['provenance'],
                'last_synced_at' => $mpStudent['last_synced_at'],
                'synced_from' => $mpStudent['synced_from'],
            ];

            if ($mpStudent['email_university'] !== null) {
                $update['email_university'] = $mpStudent['email_university'];
            }

            $existing->update($update);

            return 'UPDATED';
        }

        Student::create($mpStudent);

        return 'CREATED';
    }

    public function mapIdType(mixed $value): ?string
    {
        if (is_null($value) || ! is_numeric($value)) {
            return null;
        }

        try {
            return CcakEnumMapper::idType((int) $value)->value;
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Student related-record upserts
    // -------------------------------------------------------------------------

    private function upsertStudentBacInfo(string $studentId, array $s): void
    {
        $serie = $s['serie'] ?? null;
        $yearOfBac = $s['yearOfBac'] ?? $s['year_of_bac'] ?? null;

        if (! $serie || ! $yearOfBac) {
            return;
        }

        StudentBacInfo::updateOrCreate(
            ['student_id' => $studentId],
            [
                'serie' => $serie,
                'year_of_bac' => $yearOfBac,
                'bac_result_id' => $s['bacResultId'] ?? $s['bac_result_id'] ?? null,
                'first_round_average' => $s['firstRoundAverage'] ?? $s['first_round_average'] ?? null,
                'second_round_average' => $s['secondRoundAverage'] ?? $s['second_round_average'] ?? null,
                'bac_mention' => $s['bacMention'] ?? $s['bac_mention'] ?? null,
                'bac_institution' => $s['bacInstitution'] ?? $s['bac_institution'] ?? null,
            ]
        );
    }

    private function upsertSocialProfile(string $studentId, array $s): void
    {
        $familyStatus = $s['familyStatus'] ?? $s['family_status'] ?? null;
        $studentRegime = $s['studentRegime'] ?? $s['student_regime'] ?? null;
        $isEmployed = $s['isEmployed'] ?? $s['is_employed'] ?? null;
        $nbChildren = $s['numberOfChildren'] ?? $s['number_of_children'] ?? null;
        $socioCategory = $s['socioProfessionalCategory'] ?? $s['socio_professional_category'] ?? null;

        if ($familyStatus === null && $studentRegime === null && $isEmployed === null && $nbChildren === null && $socioCategory === null) {
            return;
        }

        SocialProfile::updateOrCreate(
            [
                'profilable_type' => Student::class,
                'profilable_id' => $studentId,
            ],
            [
                'family_status' => $familyStatus,
                'number_of_children' => $nbChildren,
                'is_employed' => $isEmployed,
                'socio_professional_category' => $socioCategory,
                'student_regime' => $studentRegime,
            ]
        );
    }

    private function upsertStudentAddresses(string $studentId, array $s): void
    {
        $city = $s['residenceCity'] ?? $s['residence_city'] ?? null;
        $region = $s['residenceRegion'] ?? $s['residence_region'] ?? null;
        $department = $s['residenceDepartment'] ?? $s['residence_department'] ?? null;

        if ($city || $region || $department) {
            Address::updateOrCreate(
                [
                    'addressable_type' => Student::class,
                    'addressable_id' => $studentId,
                    'type' => AddressType::HOME->value,
                ],
                [
                    'city' => $city,
                    'region' => $region,
                    'department' => $department,
                    'is_primary' => true,
                ]
            );
        }

        $addressTouba = $s['addressTouba'] ?? $s['address_touba'] ?? null;

        if ($addressTouba) {
            Address::updateOrCreate(
                [
                    'addressable_type' => Student::class,
                    'addressable_id' => $studentId,
                    'type' => AddressType::UNIVERSITY_CITY->value,
                ],
                [
                    'street' => $addressTouba,
                    'city' => 'Touba',
                    'is_primary' => false,
                ]
            );
        }
    }

    private function upsertGuardian(string $studentId, array $s): void
    {
        $firstName = $s['emergencyContactFirstName'] ?? null;
        $lastName = $s['emergencyContactLastName'] ?? null;
        $phone = $s['emergencyContactPhone1'] ?? null;

        if (! $firstName && ! $lastName && ! $phone) {
            return;
        }

        Guardian::updateOrCreate(
            ['student_id' => $studentId],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => trim("{$firstName} {$lastName}") ?: null,
                'relationship' => $s['emergencyContactRelation'] ?? null,
                'phone' => $phone,
                'phone_2' => $s['emergencyContactPhone2'] ?? null,
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Enrollment-specific helpers
    // -------------------------------------------------------------------------

    private function upsertEnrollment(array $raw): void
    {
        $registrationId = $raw['registrationId'];
        $studentUuid = $raw['studentUuid'];
        $programId = $raw['programId'] ?? null;
        $levelId = $raw['levelId'] ?? null;
        $academicYearId = $raw['academicYearId'];

        if (! $programId || $programId === self::NULL_UUID) {
            throw new \InvalidArgumentException(
                "Registration {$registrationId} has no academic program — skipped"
            );
        }

        if (! AcademicProgram::find($programId)) {
            throw new \InvalidArgumentException(
                "Registration {$registrationId} references unknown academic_program {$programId} — skipped (program not synced)"
            );
        }

        if (! Student::find($studentUuid)) {
            throw new \InvalidArgumentException(
                "Student {$studentUuid} not found — run syncStudents first"
            );
        }

        if (! AcademicYear::find($academicYearId)) {
            throw new \InvalidArgumentException(
                "AcademicYear {$academicYearId} not found"
            );
        }

        $enrollment = Enrollment::updateOrCreate(
            ['id' => $registrationId],
            [
                'student_id' => $studentUuid,
                'academic_program_id' => $programId,
                'academic_year_id' => $academicYearId,
                'level_id' => ($levelId && $levelId !== self::NULL_UUID) ? $levelId : null,
                'enrollment_date' => $raw['registrationDate'],
                'status' => CcakEnumMapper::registrationStatus((int) $raw['status'])->value,
                'registration_number' => $raw['registrationNumber'] ?? null,
                'notes' => $raw['notes'] ?? null,
                'is_repeating' => $raw['isRepeating'] ?? false,
                'is_medically_fit' => $raw['isMedicallyFit'] ?? null,
                'is_scholarship_holder' => $raw['isScholarshipHolder'] ?? false,
                'scholarship_type' => $raw['scholarshipType'] ?? null,
                'scholarship_amount' => $raw['scholarshipAmount'] ?? null,
                'is_registered_elsewhere' => $raw['isRegisteredElsewhere'] ?? false,
                'is_willing_to_cancel_other_registration' => $raw['isWillingToCancelOtherRegistration'] ?? null,
                'certification_file_url' => $raw['certificationFileUrl'] ?? null,
                'synced_from' => 'CCAK',
                'last_synced_at' => now(),
            ]
        );

        $this->upsertRegistrationDiploma($studentUuid, $raw);

        return $enrollment->wasRecentlyCreated ? 'CREATED' : 'UPDATED';
    }

    private function upsertRegistrationDiploma(string $studentId, array $raw): void
    {
        $name = $raw['diplomaName'] ?? null;

        if (! $name) {
            return;
        }

        PriorDiploma::updateOrCreate(
            [
                'diplomable_type' => Student::class,
                'diplomable_id' => $studentId,
                'name' => $name,
            ],
            [
                'year' => $raw['diplomaYear'] ?? null,
                'mention' => $raw['diplomaMention'] ?? null,
                'institution' => $raw['diplomaInstitution'] ?? null,
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Private helper
    // -------------------------------------------------------------------------

    /**
     * Generic sync runner: creates a SyncLog, iterates records, updates log on completion.
     *
     * @param  callable(): array  $fetchFn  Returns the raw array from CCAK
     * @param  callable(array): void  $processFn  Processes a single record (throw to count as error)
     */
    private function runSync(string $entityType, callable $fetchFn, callable $processFn): SyncLog
    {
        $log = SyncLog::create([
            'batch_date' => today(),
            'source' => 'CCAK',
            'entity_type' => $entityType,
            'started_at' => now(),
            'status' => SyncStatus::PARTIAL->value,
        ]);

        $created = 0;
        $updated = 0;
        $errors = 0;
        $total = 0;
        $errorDetails = [];

        try {
            $items = $fetchFn();
            $total = count($items);

            foreach ($items as $raw) {
                try {
                    $result = $processFn($raw);
                    if ($result === 'CREATED') {
                        $created++;
                    } else {
                        $updated++;
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $errorDetails[] = [
                        'id' => $raw['id'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                    Log::warning("SyncService[{$entityType}]: record failed", [
                        'error' => $e->getMessage(),
                        'id' => $raw['id'] ?? null,
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
            'total_created' => $created,
            'total_updated' => $updated,
            'total_errors' => $errors,
            'error_details' => $errorDetails ?: null,
            'completed_at' => now(),
            'status' => $status->value,
        ]);

        return $log->fresh();
    }
}

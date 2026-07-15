<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Course;
use App\Models\CourseUnit;
use Illuminate\Support\Facades\DB;

class MaquetteImportService
{
    /**
     * @param  array  $parsed  Output of MaquetteParserService::parse()
     * @param  array  $options  {program_id, dry_run?}
     */
    public function import(array $parsed, array $options): array
    {
        $dryRun = filter_var($options['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $result = [
            'dry_run' => $dryRun,
            'program_id' => null,
            'programs_found' => 0,
            'units_created' => 0,
            'units_skipped' => 0,
            'courses_created' => 0,
            'courses_skipped' => 0,
            'warnings' => $parsed['warnings'] ?? [],
            'errors' => array_merge($parsed['errors'] ?? []),
        ];

        if (! empty($result['errors'])) {
            return $result;
        }

        DB::beginTransaction();

        try {
            $program = AcademicProgram::find($options['program_id']);

            if (! $program) {
                throw new \InvalidArgumentException('Programme introuvable.');
            }

            $result['program_id'] = $program->id;
            $result['programs_found']++;

            foreach ($parsed['semesters'] as $semesterNum => $semester) {
                foreach ($semester['course_units'] as $unitData) {
                    [$unit, $created, $unitWarn] = $this->upsertUnit($program->id, $semesterNum, $unitData);

                    if ($unitWarn) {
                        $result['warnings'][] = $unitWarn;
                    }
                    if ($created) {
                        $result['units_created']++;
                    } else {
                        $result['units_skipped']++;
                    }

                    // Skip courses if UE belongs to a different program
                    if ($unitWarn) {
                        $result['courses_skipped'] += count($unitData['courses']);

                        continue;
                    }

                    foreach ($unitData['courses'] as $courseData) {
                        [, $courseCreated, $courseWarn] = $this->upsertCourse($unit->id, $courseData);

                        if ($courseWarn) {
                            $result['warnings'][] = $courseWarn;
                        }
                        if ($courseCreated) {
                            $result['courses_created']++;
                        } else {
                            $result['courses_skipped']++;
                        }
                    }
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function upsertUnit(string $programId, int $semester, array $data): array
    {
        $existing = CourseUnit::with('academicProgram')->where('code', $data['code'])->first();

        $attributes = [
            'name' => $data['name'],
            'semester_number' => $semester,
            'type' => in_array($data['type'], CourseUnit::TYPES) ? $data['type'] : 'OBLIGATOIRE',
        ];

        // Only overwrite credits/coefficient if the file provides real values
        if (($data['credits'] ?? null) !== null) {
            $attributes['credits'] = $data['credits'];
        }
        if (($data['coefficient'] ?? null) !== null) {
            $attributes['coefficient'] = $data['coefficient'];
        }

        if ($existing) {
            if ($existing->academic_program_id !== $programId) {
                $existingProgramName = $existing->academicProgram?->name ?? 'programme inconnu';

                return [$existing, false, "UE \"{$data['code']}\" ignorée : existe déjà dans le programme « {$existingProgramName} »."];
            }
            $existing->update($attributes);

            return [$existing, false, null];
        }

        $unit = CourseUnit::create(array_merge($attributes, [
            'code' => $data['code'],
            'academic_program_id' => $programId,
            'credits' => $data['credits'] ?? 0,
            'coefficient' => $data['coefficient'] ?? 0,
            'is_active' => true,
        ]));

        return [$unit, true, null];
    }

    private function upsertCourse(string $unitId, array $data): array
    {
        $existing = Course::where('code', $data['code'])->first();

        $attributes = [
            'name' => $data['name'],
            'hours_lecture' => $data['hours_lecture'] ?? 0,
            'hours_td' => $data['hours_td'] ?? 0,
            'hours_tp' => $data['hours_tp'] ?? 0,
            'hours_tpe' => $data['hours_tpe'] ?? 0,
            'vht' => $data['vht'] ?? 0,
        ];

        if (($data['credits'] ?? null) !== null) {
            $attributes['credits'] = $data['credits'];
        }
        if (($data['coefficient'] ?? null) !== null) {
            $attributes['coefficient'] = $data['coefficient'];
        }

        if ($existing) {
            if ($existing->course_unit_id !== $unitId) {
                return [$existing, false, "ECUE \"{$data['code']}\" ignorée : existe déjà dans une autre UE."];
            }
            $existing->update($attributes);

            return [$existing, false, null];
        }

        $course = Course::create(array_merge($attributes, [
            'code' => $data['code'],
            'course_unit_id' => $unitId,
            'credits' => $data['credits'] ?? 0,
            'coefficient' => $data['coefficient'] ?? 0,
            'is_active' => true,
        ]));

        return [$course, true, null];
    }
}

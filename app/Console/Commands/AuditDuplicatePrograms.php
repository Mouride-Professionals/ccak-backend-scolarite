<?php

namespace App\Console\Commands;

use App\Models\AcademicProgram;
use App\Models\CourseUnit;
use App\Models\Enrollment;
use Illuminate\Console\Command;

class AuditDuplicatePrograms extends Command
{
    protected $signature = 'maquette:audit-duplicate-programs';

    protected $description = 'List academic programs, per department, with their enrollment and course-unit counts, to spot duplicate program rows created by maquette imports before the program_id fix.';

    public function handle(): int
    {
        $programs = AcademicProgram::with('department')
            ->orderBy('department_id')
            ->orderBy('name')
            ->get();

        $courseUnitCounts = CourseUnit::query()
            ->selectRaw('academic_program_id, count(*) as aggregate')
            ->groupBy('academic_program_id')
            ->pluck('aggregate', 'academic_program_id');

        $enrollmentCounts = Enrollment::query()
            ->selectRaw('academic_program_id, count(*) as aggregate')
            ->groupBy('academic_program_id')
            ->pluck('aggregate', 'academic_program_id');

        $byDepartment = $programs->groupBy('department_id');
        $suspects = [];

        foreach ($byDepartment as $departmentId => $departmentPrograms) {
            if ($departmentPrograms->count() < 2) {
                continue;
            }

            $rows = $departmentPrograms->map(function (AcademicProgram $program) use ($courseUnitCounts, $enrollmentCounts) {
                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'course_units' => (int) ($courseUnitCounts[$program->id] ?? 0),
                    'enrollments' => (int) ($enrollmentCounts[$program->id] ?? 0),
                    'created_at' => $program->created_at,
                ];
            });

            $hasOrphanCourseUnits = $rows->contains(fn ($r) => $r['course_units'] > 0 && $r['enrollments'] === 0);
            $hasOrphanEnrollments = $rows->contains(fn ($r) => $r['enrollments'] > 0 && $r['course_units'] === 0);

            if (! ($hasOrphanCourseUnits && $hasOrphanEnrollments)) {
                continue;
            }

            $departmentName = $departmentPrograms->first()->department?->name ?? $departmentId;
            $suspects[] = ['department' => $departmentName, 'rows' => $rows];
        }

        if (empty($suspects)) {
            $this->info('No suspected duplicate program pairs found.');

            return self::SUCCESS;
        }

        foreach ($suspects as $suspect) {
            $this->line("<fg=yellow>Department: {$suspect['department']}</>");
            $this->table(
                ['id', 'name', 'course_units', 'enrollments', 'created_at'],
                $suspect['rows']->map(fn ($r) => [$r['id'], $r['name'], $r['course_units'], $r['enrollments'], $r['created_at']])->all()
            );
        }

        $this->warn(count($suspects).' department(s) with a suspected duplicate program pair (one row holds course units, another holds enrollments).');

        return self::SUCCESS;
    }
}

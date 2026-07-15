<?php

namespace App\Console\Commands;

use App\Models\AcademicProgram;
use App\Models\Enrollment;
use Illuminate\Console\Command;

class DeleteDuplicatePrograms extends Command
{
    protected $signature = 'maquette:delete-duplicate-programs {ids* : Academic program UUIDs to delete} {--force : Skip confirmation}';

    protected $description = 'Delete specific academic program rows (and their course units/courses via cascade) identified as duplicates by maquette:audit-duplicate-programs. Refuses to delete any program that still has enrollments.';

    public function handle(): int
    {
        $ids = $this->argument('ids');
        $programs = AcademicProgram::whereIn('id', $ids)->withCount('courseUnits')->get();

        $missing = array_diff($ids, $programs->pluck('id')->all());
        if (! empty($missing)) {
            $this->error('Program id(s) not found: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $blocked = [];
        foreach ($programs as $program) {
            $enrollmentCount = Enrollment::where('academic_program_id', $program->id)->count();
            if ($enrollmentCount > 0) {
                $blocked[] = "{$program->name} ({$program->id}) has {$enrollmentCount} enrollment(s)";
            }
        }

        if (! empty($blocked)) {
            $this->error('Refusing to delete — the following programs still have enrollments:');
            foreach ($blocked as $line) {
                $this->line("  - {$line}");
            }

            return self::FAILURE;
        }

        $this->table(
            ['id', 'name', 'course_units'],
            $programs->map(fn (AcademicProgram $p) => [$p->id, $p->name, $p->course_units_count])->all()
        );

        if (! $this->option('force') && ! $this->confirm('Delete these programs and all their course units/courses?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        foreach ($programs as $program) {
            $program->delete();
            $this->info("Deleted {$program->name} ({$program->id}).");
        }

        return self::SUCCESS;
    }
}

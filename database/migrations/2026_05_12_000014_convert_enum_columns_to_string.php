<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Convert all DB-level enum columns to plain VARCHAR strings.
 * Validation is handled at the application layer via PHP enums / casts.
 * This drops the PostgreSQL CHECK constraints that enum() generates.
 */
return new class extends Migration
{
    private array $constraints = [
        'announcements'          => ['priority'],
        'attendance_records'     => ['status'],
        'enrollments'            => ['status'],
        'guardians'              => ['relationship'],
        'grades'                 => ['type', 'status'],
        'faculty_members'        => ['rank', 'contract_type'],
        'faculty_documents'      => ['type', 'status'],
        'students'               => ['gender', 'status'],
        'holidays'               => ['type'],
        'course_enrollments'     => ['status'],
        'deliberation_sessions'  => ['status'],
        'rooms'                  => ['type'],
        'faculty_contracts'      => ['contract_type', 'status'],
        'course_units'           => ['type'],
        'semester_results'       => ['decision'],
        'generated_documents'    => ['type', 'status'],
        'deliberation_results'   => ['decision', 'honor_level'],
        'academic_programs'      => ['level'],
        'teaching_assignments'   => ['role'],
        'documents'              => ['type', 'status'],
    ];

    public function up(): void
    {
        foreach ($this->constraints as $table => $columns) {
            foreach ($columns as $column) {
                // Drop the CHECK constraint PostgreSQL creates for enum columns.
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_{$column}_check");
                // Re-type to VARCHAR (no-op if already string, safe to run repeatedly).
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE VARCHAR(255)");
            }
        }
    }

    public function down(): void
    {
        // Intentionally empty — we do not restore enum CHECK constraints.
    }
};

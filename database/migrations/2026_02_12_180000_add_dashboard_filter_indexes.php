<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->index(
                ['academic_year_id', 'status', 'enrollment_date'],
                'enrollments_year_status_date_idx'
            );
        });

        Schema::table('faculty_members', function (Blueprint $table): void {
            $table->index(
                ['department_id', 'is_active'],
                'faculty_members_department_active_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropIndex('enrollments_year_status_date_idx');
        });

        Schema::table('faculty_members', function (Blueprint $table): void {
            $table->dropIndex('faculty_members_department_active_idx');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('exam_number', 20)->nullable()->after('registration_number');
            $table->unique(['academic_year_id', 'exam_number'], 'enrollments_year_exam_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique('enrollments_year_exam_number_unique');
            $table->dropColumn('exam_number');
        });
    }
};

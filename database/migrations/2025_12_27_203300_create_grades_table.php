<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_enrollment_id');
            $table->uuid('student_id');
            $table->uuid('course_id');
            $table->enum('type', ['CC', 'EXAM', 'TP', 'ORAL']);
            $table->decimal('score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->decimal('weight', 5, 2);
            $table->uuid('entered_by');
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'VALIDATED', 'PUBLISHED'])->default('DRAFT');
            $table->timestamp('entered_at')->useCurrent();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->foreign('course_enrollment_id')->references('id')->on('course_enrollments')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('entered_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};

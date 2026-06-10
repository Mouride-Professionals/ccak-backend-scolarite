<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id');
            $table->uuid('faculty_member_id');
            $table->uuid('academic_year_id');
            $table->string('title');
            $table->string('type')->default('WRITTEN'); // AssessmentType enum
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('room')->nullable();
            $table->decimal('coefficient', 3, 2)->nullable();
            $table->boolean('is_grades_published')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('faculty_member_id')->references('id')->on('faculty_members')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();

            $table->index(['course_id', 'academic_year_id']);
            $table->index(['faculty_member_id', 'academic_year_id']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};

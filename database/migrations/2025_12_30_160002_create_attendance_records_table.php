<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_log_id');
            $table->uuid('student_id');
            $table->enum('status', ['PRESENT', 'ABSENT', 'LATE', 'EXCUSED']);
            $table->timestamp('marked_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('absence_count')->default(0);
            $table->boolean('is_dispensed')->default(false);
            $table->timestamps();

            $table->foreign('course_log_id')->references('id')->on('course_logs')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->unique(['course_log_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};

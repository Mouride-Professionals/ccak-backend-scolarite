<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id');
            $table->uuid('faculty_member_id');
            $table->uuid('room_id');
            $table->uuid('activity_type_id');
            $table->uuid('academic_year_id');
            $table->unsignedTinyInteger('semester_number');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('recurrence_pattern')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('faculty_member_id')->references('id')->on('faculty_members')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
            $table->foreign('activity_type_id')->references('id')->on('activity_types')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->index(['academic_year_id', 'semester_number', 'day_of_week']);
            $table->index(['room_id', 'day_of_week']);
            $table->index(['faculty_member_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};

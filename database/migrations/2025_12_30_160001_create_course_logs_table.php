<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('schedule_id');
            $table->uuid('faculty_member_id');
            $table->uuid('created_by_user_id');
            $table->date('session_date');
            $table->json('topics')->nullable();
            $table->json('chapters')->nullable();
            $table->json('objectives')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
            $table->foreign('faculty_member_id')->references('id')->on('faculty_members')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['schedule_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_schedule_invigilators', function (Blueprint $table) {
            $table->uuid('exam_schedule_id');
            $table->uuid('faculty_member_id');

            $table->primary(['exam_schedule_id', 'faculty_member_id']);

            $table->foreign('exam_schedule_id')->references('id')->on('exam_schedules')->cascadeOnDelete();
            $table->foreign('faculty_member_id')->references('id')->on('faculty_members')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_schedule_invigilators');
    }
};

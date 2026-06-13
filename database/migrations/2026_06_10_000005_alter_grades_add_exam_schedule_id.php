<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->uuid('exam_schedule_id')->nullable()->after('assessment_id');
            $table->foreign('exam_schedule_id')->references('id')->on('exam_schedules')->nullOnDelete();
            $table->index('exam_schedule_id');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign(['exam_schedule_id']);
            $table->dropIndex(['exam_schedule_id']);
            $table->dropColumn('exam_schedule_id');
        });
    }
};

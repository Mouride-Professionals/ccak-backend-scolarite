<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->uuid('assessment_id')->nullable()->after('course_id');
            $table->foreign('assessment_id')->references('id')->on('assessments')->nullOnDelete();
            $table->index('assessment_id');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign(['assessment_id']);
            $table->dropIndex(['assessment_id']);
            $table->dropColumn('assessment_id');
        });
    }
};

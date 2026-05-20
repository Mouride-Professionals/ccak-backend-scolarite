<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('academic_year_id');
            $table->unsignedTinyInteger('semester_number');
            $table->string('name');
            $table->string('type')->default('NORMAL'); // NORMAL | RATTRAPAGE
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('DRAFT'); // DRAFT | PUBLISHED | CLOSED
            $table->timestamps();

            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->index(['academic_year_id', 'semester_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};

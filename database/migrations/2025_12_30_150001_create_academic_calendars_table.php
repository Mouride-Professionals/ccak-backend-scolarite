<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_calendars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('academic_year_id')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->json('working_days');
            $table->json('weekend_days')->nullable();
            $table->json('hour_slots');
            $table->json('break_slots')->nullable();
            $table->timestamps();

            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_calendars');
    }
};

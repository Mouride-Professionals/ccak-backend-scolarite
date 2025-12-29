<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deliberation_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('academic_program_id');
            $table->uuid('academic_year_id');
            $table->unsignedTinyInteger('semester');
            $table->string('session_name');
            $table->date('session_date');
            $table->enum('status', ['SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'CLOSED'])->default('SCHEDULED');
            $table->uuid('presided_by');
            $table->json('jury_members')->nullable();
            $table->timestamps();

            $table->index(['academic_program_id', 'academic_year_id', 'semester']);
            $table->index('status');
            $table->index('session_date');
            $table->index('presided_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliberation_sessions');
    }
};

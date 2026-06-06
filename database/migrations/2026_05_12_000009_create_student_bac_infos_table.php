<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_bac_infos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->string('serie');
            $table->string('year_of_bac');
            $table->string('bac_result_id')->nullable();
            $table->decimal('first_round_average', 5, 2)->nullable();
            $table->decimal('second_round_average', 5, 2)->nullable();
            $table->string('bac_mention')->nullable();
            $table->string('bac_institution')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_bac_infos');
    }
};

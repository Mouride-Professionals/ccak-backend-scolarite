<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deliberation_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('deliberation_session_id');
            $table->uuid('student_id');
            $table->enum('decision', ['ADMITTED', 'ADMITTED_COMPENSATION', 'RESIT', 'FAILED', 'EXCLUDED']);
            $table->text('jury_remarks')->nullable();
            $table->boolean('is_with_honors')->default(false);
            $table->enum('honor_level', ['PASSABLE', 'ASSEZ_BIEN', 'BIEN', 'TRES_BIEN'])->nullable();
            $table->timestamps();

            $table->foreign('deliberation_session_id')
                ->references('id')
                ->on('deliberation_sessions')
                ->cascadeOnDelete();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();

            $table->unique(['deliberation_session_id', 'student_id']);
            $table->index('decision');
            $table->index('is_with_honors');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliberation_results');
    }
};

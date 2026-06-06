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
        Schema::create('deliberation_session_jury', function (Blueprint $table) {
            $table->uuid('deliberation_session_id');
            $table->uuid('faculty_member_id');
            $table->primary(['deliberation_session_id', 'faculty_member_id']);

            $table->foreign('deliberation_session_id')
                ->references('id')->on('deliberation_sessions')
                ->cascadeOnDelete();
            $table->foreign('faculty_member_id')
                ->references('id')->on('faculty_members')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliberation_session_jury');
    }
};

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
        Schema::create('faculty_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('staff_number')->unique();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->uuid('department_id')->nullable();
            $table->enum('rank', ['PROFESSEUR', 'MAITRE_CONF', 'MAITRE_ASS', 'ASSISTANT', 'VACATAIRE'])->nullable();
            $table->enum('contract_type', ['PERMANENT', 'TEMPORARY', 'HOURLY'])->nullable();
            $table->date('hire_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faculty_member');
    }
};

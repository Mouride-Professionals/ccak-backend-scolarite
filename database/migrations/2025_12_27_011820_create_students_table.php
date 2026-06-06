<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique()->nullable();

            // Informations personnelles
            $table->string('student_number')->unique();
            $table->string('full_name');
            $table->enum('gender', ['M', 'F'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('phone')->nullable();

            // Contacts d'urgence
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            // Adresse
            $table->text('address')->nullable();

            // Photo
            $table->string('photo_url')->nullable();

            // Statut académique
            $table->enum('status', [
                'ACTIVE',
                'SUSPENDED',
                'GRADUATED',
                'WITHDRAWN',
                'EXPELLED',
            ])->default('ACTIVE');

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('student_number');
            $table->index('full_name');
            $table->index('status');
            $table->index(['status', 'created_at']);

            // Foreign key vers users (si vous avez la table users)
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

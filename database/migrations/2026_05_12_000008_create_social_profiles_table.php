<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('profilable');
            $table->string('family_status')->nullable();
            $table->unsignedSmallInteger('number_of_children')->nullable();
            $table->boolean('is_employed')->nullable();
            $table->string('socio_professional_category')->nullable();
            $table->string('student_regime')->nullable();
            $table->timestamps();

            $table->unique(['profilable_type', 'profilable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_profiles');
    }
};

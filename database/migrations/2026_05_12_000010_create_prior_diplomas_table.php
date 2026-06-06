<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prior_diplomas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('diplomable');
            $table->string('name');
            $table->string('year')->nullable();
            $table->string('mention')->nullable();
            $table->string('institution')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prior_diplomas');
    }
};

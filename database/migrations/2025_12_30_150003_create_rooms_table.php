<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('room_number')->unique();
            $table->string('name')->nullable();
            $table->string('building')->nullable();
            $table->unsignedInteger('capacity');
            $table->enum('type', ['LECTURE_HALL', 'LAB', 'TD_ROOM']);
            $table->json('equipment')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

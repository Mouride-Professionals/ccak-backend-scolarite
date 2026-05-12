<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('batch_date');
            $table->string('source')->default('CCAK');
            $table->string('entity_type');
            $table->integer('total_received')->default(0);
            $table->integer('total_created')->default(0);
            $table->integer('total_updated')->default(0);
            $table->integer('total_errors')->default(0);
            $table->json('error_details')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};

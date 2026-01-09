<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('faculty_member_id');
            $table->enum('contract_type', ['PERMANENT', 'TEMPORARY', 'HOURLY']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->text('terms')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->enum('status', ['DRAFT', 'ACTIVE', 'EXPIRED', 'TERMINATED'])->default('DRAFT');
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->foreign('faculty_member_id')->references('id')->on('faculty_members')->cascadeOnDelete();
            $table->index(['faculty_member_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_contracts');
    }
};

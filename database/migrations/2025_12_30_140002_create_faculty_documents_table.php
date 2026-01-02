<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('faculty_member_id');
            $table->enum('type', ['CV', 'DIPLOMA', 'CNI', 'OTHER']);
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->uuid('reviewed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('faculty_member_id')->references('id')->on('faculty_members')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('admins')->nullOnDelete();
            $table->index(['faculty_member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_documents');
    }
};

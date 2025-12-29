<?php

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
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
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->uuid(column: 'id')->primary();
            $table->uuid('student_id');
            $table->enum('type', DocumentType::cases());
            $table->string('document_number')->unique();
            $table->string('file_path');
            $table->uuid('generated_by');
            $table->json('metadata');
            $table->enum('status', DocumentStatus::cases());
            $table->timestamp('generated_at');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('generated_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated__documents');
    }
};

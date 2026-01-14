<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->unsignedBigInteger('media_id')->nullable()->after('file_name');
        });

        Schema::table('faculty_documents', function (Blueprint $table): void {
            $table->unsignedBigInteger('media_id')->nullable()->after('file_name');
        });

        Schema::table('generated_documents', function (Blueprint $table): void {
            $table->unsignedBigInteger('media_id')->nullable()->after('file_path');
        });

        if (Schema::hasTable('media')) {
            Schema::table('documents', function (Blueprint $table): void {
                $table->foreign('media_id')->references('id')->on('media')->nullOnDelete();
            });

            Schema::table('faculty_documents', function (Blueprint $table): void {
                $table->foreign('media_id')->references('id')->on('media')->nullOnDelete();
            });

            Schema::table('generated_documents', function (Blueprint $table): void {
                $table->foreign('media_id')->references('id')->on('media')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            if (Schema::hasTable('media')) {
                $table->dropForeign(['media_id']);
            }
            $table->dropColumn('media_id');
        });

        Schema::table('faculty_documents', function (Blueprint $table): void {
            if (Schema::hasTable('media')) {
                $table->dropForeign(['media_id']);
            }
            $table->dropColumn('media_id');
        });

        Schema::table('generated_documents', function (Blueprint $table): void {
            if (Schema::hasTable('media')) {
                $table->dropForeign(['media_id']);
            }
            $table->dropColumn('media_id');
        });
    }
};

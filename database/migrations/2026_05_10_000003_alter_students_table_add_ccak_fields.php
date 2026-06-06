<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('full_name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('ine')->nullable();
            $table->string('registration_number')->nullable();
            $table->uuid('level_id')->nullable();
            $table->string('provenance')->nullable();
            $table->string('synced_from')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropColumn(['first_name', 'last_name', 'ine', 'registration_number', 'level_id', 'provenance', 'synced_from', 'last_synced_at']);
        });
    }
};

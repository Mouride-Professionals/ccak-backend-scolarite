<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('student_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone_2')->nullable()->after('phone');
            // Widen relationship from enum to string to support free-form values.
            $table->string('relationship')->change();
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'phone_2']);
            $table->enum('relationship', ['FATHER', 'MOTHER', 'GUARDIAN'])->change();
        });
    }
};

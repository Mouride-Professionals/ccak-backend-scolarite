<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_units', function (Blueprint $table) {
            $table->decimal('coefficient', 6, 2)->nullable()->after('credits');
        });
    }

    public function down(): void
    {
        Schema::table('course_units', function (Blueprint $table) {
            $table->dropColumn('coefficient');
        });
    }
};

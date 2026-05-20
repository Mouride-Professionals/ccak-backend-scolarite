<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedSmallInteger('hours_tpe')->default(0)->after('hours_tp');
            $table->unsignedSmallInteger('vht')->default(0)->after('hours_tpe');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['hours_tpe', 'vht']);
        });
    }
};

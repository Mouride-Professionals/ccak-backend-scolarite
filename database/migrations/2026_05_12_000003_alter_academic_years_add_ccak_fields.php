<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
            $table->string('status')->nullable()->after('code');
            $table->string('synced_from')->nullable()->after('status');
            $table->timestamp('last_synced_at')->nullable()->after('synced_from');
        });
    }

    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn(['code', 'status', 'synced_from', 'last_synced_at']);
        });
    }
};

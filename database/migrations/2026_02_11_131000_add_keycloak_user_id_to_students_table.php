<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            if (! Schema::hasColumn('students', 'keycloak_user_id')) {
                $table->uuid('keycloak_user_id')->nullable()->unique()->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            if (Schema::hasColumn('students', 'keycloak_user_id')) {
                $table->dropUnique(['keycloak_user_id']);
                $table->dropColumn('keycloak_user_id');
            }
        });
    }
};

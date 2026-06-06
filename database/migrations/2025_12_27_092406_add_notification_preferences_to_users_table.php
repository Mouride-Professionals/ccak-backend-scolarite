<?php

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
        Schema::table('users', function (Blueprint $table) {
            // 1. Préférences par type de notification (JSON)
            $table->json('notification_preferences')->nullable()->after('email');

            // 2. Fréquence des emails (instant, daily, weekly)
            $table->string('email_frequency')->default('instant')->after('notification_preferences');

            // 3. Option de résumé (digest) activée ou non
            $table->boolean('enable_digest')->default(false)->after('email_frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notification_preferences', 'email_frequency', 'enable_digest']);
        });
    }
};

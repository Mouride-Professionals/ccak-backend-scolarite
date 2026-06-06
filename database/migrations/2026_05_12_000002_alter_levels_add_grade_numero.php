<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->uuid('degree_cycle_id')->nullable()->after('code');
            $table->unsignedSmallInteger('numero')->nullable()->after('degree_cycle_id');
            $table->integer('duration_semesters')->nullable()->change();

            $table->foreign('degree_cycle_id')->references('id')->on('degree_cycles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->dropForeign(['degree_cycle_id']);
            $table->dropColumn(['degree_cycle_id', 'numero']);
        });
    }
};

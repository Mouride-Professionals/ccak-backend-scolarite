<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('phone');
            $table->string('email_university')->nullable()->unique()->after('email');
            $table->string('phone_2')->nullable()->after('phone');
            $table->string('type_of_id')->nullable()->after('nationality'); // IDType enum
            $table->string('id_details')->nullable()->after('type_of_id');

            // level_id moves to enrollments — drop FK then column
            $table->dropForeign(['level_id']);
            $table->dropColumn('level_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['email', 'email_university', 'phone_2', 'type_of_id', 'id_details']);
            $table->uuid('level_id')->nullable();
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('set null');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_assignments', function (Blueprint $table) {
            $table->decimal('hours_cm', 6, 2)->nullable()->after('hours_assigned');
            $table->decimal('hours_td', 6, 2)->nullable()->after('hours_cm');
            $table->date('planned_start_date')->nullable()->after('hours_td');
            $table->date('effective_start_date')->nullable()->after('planned_start_date');
            $table->date('end_date')->nullable()->after('effective_start_date');
            $table->string('status')->default('NOT_STARTED')->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'hours_cm',
                'hours_td',
                'planned_start_date',
                'effective_start_date',
                'end_date',
                'status',
            ]);
        });
    }
};

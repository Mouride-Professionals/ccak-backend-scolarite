<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // level_id moved from students
            $table->uuid('level_id')->nullable()->after('academic_year_id');
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('set null');

            // Status aligned to RegistrationStatus enum values
            // Existing column stays; cast on model will enforce the enum

            // Registration info
            $table->string('registration_number')->nullable()->unique()->after('status');
            $table->text('notes')->nullable()->after('registration_number');

            // Specific registration flags
            $table->boolean('is_repeating')->default(false)->after('notes');
            $table->boolean('is_medically_fit')->nullable()->after('is_repeating');
            $table->boolean('is_registered_elsewhere')->default(false)->after('is_medically_fit');
            $table->boolean('is_willing_to_cancel_other_registration')->nullable()->after('is_registered_elsewhere');

            // Scholarship details (rename is_scholarship → is_scholarship_holder)
            $table->renameColumn('is_scholarship', 'is_scholarship_holder');
            $table->string('scholarship_type')->nullable()->after('is_scholarship_holder');
            $table->decimal('scholarship_amount', 12, 2)->nullable()->after('scholarship_type');

            // Backend-generated file URL
            $table->string('certification_file_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropColumn([
                'level_id', 'registration_number', 'notes',
                'is_repeating', 'is_medically_fit',
                'is_registered_elsewhere', 'is_willing_to_cancel_other_registration',
                'scholarship_type', 'scholarship_amount', 'certification_file_url',
            ]);
            $table->renameColumn('is_scholarship_holder', 'is_scholarship');
        });
    }
};

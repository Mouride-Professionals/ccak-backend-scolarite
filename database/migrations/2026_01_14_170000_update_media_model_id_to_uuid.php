<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media')) {
            return;
        }

        $count = DB::table('media')->count();
        if ($count > 0) {
            throw new RuntimeException('Media table contains data. Please migrate existing media records before altering model_id to UUID.');
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS media_model_type_model_id_index');
            DB::statement('ALTER TABLE media ALTER COLUMN model_id TYPE uuid USING NULL::uuid');
            DB::statement('CREATE INDEX media_model_type_model_id_index ON media (model_type, model_id)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('media')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS media_model_type_model_id_index');
            DB::statement('ALTER TABLE media ALTER COLUMN model_id TYPE bigint USING NULL::bigint');
            DB::statement('CREATE INDEX media_model_type_model_id_index ON media (model_type, model_id)');
        }
    }
};

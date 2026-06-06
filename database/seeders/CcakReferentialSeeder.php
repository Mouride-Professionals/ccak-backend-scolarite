<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds generic academic structure only.
 * Institution-specific data (faculties, departments, programs, academic years)
 * is synced via: php artisan sync:ccak-students
 * once CCAK_API_URL and CCAK_API_KEY are configured.
 */
class CcakReferentialSeeder extends Seeder
{
    public function run(): void
    {
        // No hardcoded data — use sync:ccak-students to populate referential data.
    }
}

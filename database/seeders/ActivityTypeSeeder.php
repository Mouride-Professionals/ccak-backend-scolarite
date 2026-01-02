<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use Illuminate\Database\Seeder;

class ActivityTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Cours Magistral', 'code' => 'CM', 'duration_minutes' => 120, 'color' => '#2F6FEB'],
            ['name' => 'Travaux Dirigés', 'code' => 'TD', 'duration_minutes' => 120, 'color' => '#1F9D55'],
            ['name' => 'Travaux Pratiques', 'code' => 'TP', 'duration_minutes' => 120, 'color' => '#F59E0B'],
            ['name' => 'Sortie', 'code' => 'SORTIE', 'duration_minutes' => 180, 'color' => '#8B5CF6'],
            ['name' => 'Devoir', 'code' => 'DEVOIR', 'duration_minutes' => 90, 'color' => '#EF4444'],
            ['name' => 'Examen', 'code' => 'EXAMEN', 'duration_minutes' => 120, 'color' => '#111827'],
        ];

        foreach ($rows as $row) {
            ActivityType::firstOrCreate(
                ['code' => $row['code']],
                $row
            );
        }
    }
}

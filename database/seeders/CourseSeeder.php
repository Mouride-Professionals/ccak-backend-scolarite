<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseUnit;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        if (CourseUnit::count() === 0) {
            $this->call(CourseUnitSeeder::class);
        }

        $courseUnits = CourseUnit::all();
        if ($courseUnits->isEmpty()) {
            $this->command->warn('No course units found. Skipping courses.');

            return;
        }

        $courseUnits->each(function (CourseUnit $courseUnit) {
            $codeBase = 'EC-'.$courseUnit->code;
            $name = $courseUnit->name;
            $credits = (int) $courseUnit->credits;
            $hours = $this->hoursForUnit($courseUnit->code, $name);

            Course::firstOrCreate(
                ['code' => $codeBase],
                [
                    'course_unit_id' => $courseUnit->id,
                    'name' => $name,
                    'description' => 'Cours principal pour '.$name,
                    'credits' => $credits,
                    'hours_lecture' => $hours['lecture'],
                    'hours_td' => $hours['td'],
                    'hours_tp' => $hours['tp'],
                    'coefficient' => $this->coefficientForCredits($credits),
                    'prerequisites' => [],
                    'is_active' => true,
                ]
            );
        });
    }

    /**
     * @return array{lecture: int, td: int, tp: int}
     */
    private function hoursForUnit(string $code, string $name): array
    {
        $lower = strtolower($code.' '.$name);
        $isScience = str_contains($lower, 'info')
            || str_contains($lower, 'chimie')
            || str_contains($lower, 'physique')
            || str_contains($lower, 'bio')
            || str_contains($lower, 'genetique')
            || str_contains($lower, 'micro');

        return [
            'lecture' => 30,
            'td' => 15,
            'tp' => $isScience ? 15 : 0,
        ];
    }

    private function coefficientForCredits(int $credits): float
    {
        if ($credits >= 6) {
            return 2.0;
        }

        if ($credits >= 4) {
            return 1.5;
        }

        return 1.0;
    }
}

<?php

namespace Database\Factories;

use App\Models\AcademicProgram;
use App\Models\CourseUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseUnit>
 */
class CourseUnitFactory extends Factory
{
    protected $model = CourseUnit::class;

    public function definition(): array
    {
        static $sequence = 0;
        $units = [
            ['code' => 'UE-INF-ALG', 'name' => 'Algorithmique', 'semester' => 1, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
            ['code' => 'UE-INF-PROG1', 'name' => 'Programmation 1', 'semester' => 1, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
            ['code' => 'UE-INF-SD', 'name' => 'Structures de donnees', 'semester' => 2, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
            ['code' => 'UE-INF-BDD', 'name' => 'Bases de donnees', 'semester' => 2, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
            ['code' => 'UE-MAT-AN1', 'name' => 'Analyse 1', 'semester' => 1, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
            ['code' => 'UE-MAT-AL1', 'name' => 'Algebre 1', 'semester' => 1, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
            ['code' => 'UE-ECO-MICRO', 'name' => 'Microeconomie', 'semester' => 1, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
            ['code' => 'UE-ECO-MACRO', 'name' => 'Macroeconomie', 'semester' => 1, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
            ['code' => 'UE-GES-MNG', 'name' => 'Management', 'semester' => 1, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
            ['code' => 'UE-DRT-INTRO', 'name' => 'Introduction au droit', 'semester' => 1, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
            ['code' => 'UE-LSH-LITF', 'name' => 'Litterature francaise', 'semester' => 1, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
            ['code' => 'UE-SAN-EPI', 'name' => 'Epidemiologie', 'semester' => 1, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
            ['code' => 'UE-COM-ANAL', 'name' => 'Comptabilite analytique', 'semester' => 2, 'credits' => 6, 'type' => 'OBLIGATOIRE'],
        ];
        $unit = $units[$sequence % count($units)];
        $sequence++;

        return [
            'academic_program_id' => AcademicProgram::factory(),
            'code' => $unit['code'],
            'name' => $unit['name'],
            'semester_number' => $unit['semester'],
            'credits' => $unit['credits'],
            'type' => $unit['type'],
            'is_active' => true,
        ];
    }
}

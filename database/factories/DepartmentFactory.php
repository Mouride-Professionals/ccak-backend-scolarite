<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        static $sequence = 0;
        $departments = [
            ['code' => 'INF', 'name' => 'Informatique'],
            ['code' => 'MAT', 'name' => 'Mathematiques'],
            ['code' => 'PHY', 'name' => 'Physique'],
            ['code' => 'CHI', 'name' => 'Chimie'],
            ['code' => 'BIO', 'name' => 'Biologie'],
            ['code' => 'LDM', 'name' => 'Lettres Modernes'],
            ['code' => 'HIS', 'name' => 'Histoire'],
            ['code' => 'GEO', 'name' => 'Geographie'],
            ['code' => 'PHI', 'name' => 'Philosophie'],
            ['code' => 'DRP', 'name' => 'Droit Public'],
            ['code' => 'DRI', 'name' => 'Droit Prive'],
            ['code' => 'POL', 'name' => 'Sciences Politiques'],
            ['code' => 'ECO', 'name' => 'Economie'],
            ['code' => 'GES', 'name' => 'Gestion'],
            ['code' => 'COM', 'name' => 'Comptabilite'],
            ['code' => 'MED', 'name' => 'Medecine'],
            ['code' => 'PHA', 'name' => 'Pharmacie'],
            ['code' => 'SPU', 'name' => 'Sante Publique'],
        ];
        $department = $departments[$sequence % count($departments)];
        $sequence++;

        return [
            'faculty_id' => Faculty::factory(),
            'name' => $department['name'],
            'code' => $department['code'],
            'head_id' => null,
            'is_active' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Faculty>
 */
class FacultyFactory extends Factory
{
    protected $model = Faculty::class;

    public function definition(): array
    {
        static $sequence = 0;
        $faculties = [
            ['code' => 'FST', 'name' => 'Faculte des Sciences et Technologies'],
            ['code' => 'FLSH', 'name' => 'Faculte des Lettres et Sciences Humaines'],
            ['code' => 'FSJP', 'name' => 'Faculte des Sciences Juridiques et Politiques'],
            ['code' => 'FSEG', 'name' => 'Faculte des Sciences Economiques et de Gestion'],
            ['code' => 'FSS', 'name' => 'Faculte des Sciences de la Sante'],
        ];
        $faculty = $faculties[$sequence % count($faculties)];
        $sequence++;

        return [
            'name' => $faculty['name'],
            'code' => $faculty['code'],
            'dean_id' => null,
            'is_active' => true,
        ];
    }
}

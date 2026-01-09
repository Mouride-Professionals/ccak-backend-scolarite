<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        static $sequence = 0;
        $courses = [
            ['code' => 'EC-INF-ALG', 'name' => 'Algorithmique et programmation', 'description' => 'Bases de l algorithmique et introduction a la programmation.'],
            ['code' => 'EC-INF-BDD', 'name' => 'Bases de donnees', 'description' => 'Modelisation relationnelle et SQL.'],
            ['code' => 'EC-MAT-AN1', 'name' => 'Analyse 1', 'description' => 'Fonctions, limites et derivation.'],
            ['code' => 'EC-MAT-AL1', 'name' => 'Algebre 1', 'description' => 'Espaces vectoriels et applications lineaires.'],
            ['code' => 'EC-PHY-MEC', 'name' => 'Mecanique', 'description' => 'Cinématique et dynamique du point.'],
            ['code' => 'EC-ECO-MICRO', 'name' => 'Microeconomie', 'description' => 'Comportements du consommateur et du producteur.'],
            ['code' => 'EC-GES-MNG', 'name' => 'Management', 'description' => 'Fondamentaux du management des organisations.'],
            ['code' => 'EC-DRT-INTRO', 'name' => 'Introduction au droit', 'description' => 'Sources du droit et organisation judiciaire.'],
            ['code' => 'EC-LSH-LITF', 'name' => 'Litterature francaise', 'description' => 'Panorama des auteurs et courants litteraires.'],
            ['code' => 'EC-SAN-EPI', 'name' => 'Epidemiologie', 'description' => 'Methodes epidemiologiques de base.'],
            ['code' => 'EC-COM-ANAL', 'name' => 'Comptabilite analytique', 'description' => 'Calcul des couts et analyse de gestion.'],
        ];
        $course = $courses[$sequence % count($courses)];
        $sequence++;

        return [
            'course_unit_id' => CourseUnit::factory(),
            'code' => $course['code'],
            'name' => $course['name'],
            'description' => $course['description'],
            'credits' => fake()->numberBetween(2, 6),
            'hours_lecture' => fake()->numberBetween(15, 45),
            'hours_td' => fake()->numberBetween(0, 30),
            'hours_tp' => fake()->numberBetween(0, 30),
            'coefficient' => fake()->randomFloat(2, 1, 3),
            'prerequisites' => [],
            'is_active' => true,
        ];
    }
}

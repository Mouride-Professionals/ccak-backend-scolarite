<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\FacultyMember;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FacultyMember>
 */
class FacultyMemberFactory extends Factory
{
    protected $model = FacultyMember::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 1;
        $maleFirstNames = ['Mamadou', 'Abdou', 'Cheikh', 'Ousmane', 'Ibrahima', 'Moussa', 'Alioune', 'Bamba'];
        $femaleFirstNames = ['Aminata', 'Aissatou', 'Fatou', 'Khadija', 'Mariama', 'Coumba', 'Sokhna', 'Rama'];
        $lastNames = ['Ndiaye', 'Diop', 'Ba', 'Sow', 'Fall', 'Gueye', 'Cisse', 'Seck', 'Sy', 'Sarr'];
        $gender = $this->faker->randomElement(['M', 'F']);
        $firstName = $gender === 'F'
            ? $this->faker->randomElement($femaleFirstNames)
            : $this->faker->randomElement($maleFirstNames);
        $fullName = $firstName . ' ' . $this->faker->randomElement($lastNames);
        $phonePrefix = $this->faker->randomElement(['70', '75', '76', '77', '78']);

        return [
            'id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'staff_number' => 'FM-' . str_pad((string) $sequence++, 3, '0', STR_PAD_LEFT),
            'full_name' => $fullName,
            'phone' => $phonePrefix . sprintf('%07d', rand(0, 9999999)),
            'address' => $this->faker->randomElement(['Dakar', 'Thies', 'Saint-Louis', 'Kaolack', 'Ziguinchor']),
            'department_id' => Department::factory(),
            'rank' => $this->faker->randomElement(['PROFESSEUR', 'MAITRE_CONF', 'MAITRE_ASS', 'ASSISTANT']),
            'contract_type' => $this->faker->randomElement(['PERMANENT', 'TEMPORARY']),
            'hire_date' => $this->faker->date(),
            'is_active' => true,
        ];
    }
}

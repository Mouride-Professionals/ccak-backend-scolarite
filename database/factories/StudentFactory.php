<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 1;
        $year = date('Y');
        $cities = ['Dakar', 'Pikine', 'Guediawaye', 'Rufisque', 'Thies', 'Mbour', 'Saint-Louis', 'Kaolack', 'Ziguinchor', 'Diourbel'];
        $maleFirstNames = ['Mamadou', 'Abdou', 'Cheikh', 'Ousmane', 'Ibrahima', 'Moussa', 'Alioune', 'Bamba', 'Amadou', 'Serigne'];
        $femaleFirstNames = ['Aminata', 'Aissatou', 'Fatou', 'Khadija', 'Mariama', 'Coumba', 'Sokhna', 'Rama', 'Awa', 'Ndeye'];
        $lastNames = ['Ndiaye', 'Diop', 'Ba', 'Sow', 'Fall', 'Gueye', 'Cisse', 'Seck', 'Sy', 'Sarr'];
        $gender = $this->faker->randomElement(['M', 'F']);
        $firstName = $gender === 'F'
            ? $this->faker->randomElement($femaleFirstNames)
            : $this->faker->randomElement($maleFirstNames);
        $fullName = $firstName . ' ' . $this->faker->randomElement($lastNames);
        $phonePrefix = $this->faker->randomElement(['70', '75', '76', '77', '78']);

        return [
            'user_id' => User::factory(),
            'student_number' => "UCAK{$year}" . str_pad((string)$sequence++, 3, '0', STR_PAD_LEFT),
            'full_name' => $fullName,
            'gender' => $gender,
            'date_of_birth' => $this->faker->dateTimeBetween('-25 years', '-18 years'),
            'place_of_birth' => $this->faker->randomElement($cities),
            'nationality' => 'Senegalaise',
            'phone' => $phonePrefix . sprintf('%07d', rand(0, 9999999)),
            'emergency_contact_name' => $this->faker->randomElement($maleFirstNames) . ' ' . $this->faker->randomElement($lastNames),
            'emergency_contact_phone' => $phonePrefix . sprintf('%07d', rand(0, 9999999)),
            'address' => $this->faker->randomElement($cities) . ', Senegal',
            'photo_url' => null,
            'status' => $this->faker->randomElement(Student::getStatuses()),
        ];
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'status' => Student::STATUS_ACTIVE,
        ]);
    }

    public function graduated(): static
    {
        return $this->state(fn() => [
            'status' => Student::STATUS_GRADUATED,
        ]);
    }
}

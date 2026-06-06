<?php

namespace Database\Factories;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guardian>
 */
class GuardianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $maleFirstNames = ['Mamadou', 'Abdou', 'Cheikh', 'Ousmane', 'Ibrahima', 'Moussa', 'Alioune', 'Babacar'];
        $femaleFirstNames = ['Aminata', 'Aissatou', 'Fatou', 'Khadija', 'Mariama', 'Coumba', 'Sokhna', 'Rama'];
        $lastNames = ['Ndiaye', 'Diop', 'Ba', 'Sow', 'Fall', 'Gueye', 'Cisse', 'Seck', 'Sy', 'Sarr'];
        $cities = ['Dakar', 'Pikine', 'Thies', 'Mbour', 'Saint-Louis', 'Kaolack', 'Ziguinchor', 'Diourbel'];
        $occupations = [
            'Commercant',
            'Enseignant',
            'Infirmier',
            'Agent administratif',
            'Chauffeur',
            'Artisan',
            'Agriculteur',
            'Entrepreneur',
            'Technicien',
        ];
        $gender = $this->faker->randomElement(['M', 'F']);
        $firstName = $gender === 'F'
            ? $this->faker->randomElement($femaleFirstNames)
            : $this->faker->randomElement($maleFirstNames);
        $fullName = $firstName.' '.$this->faker->randomElement($lastNames);
        $email = strtolower(str_replace(' ', '.', $fullName)).'@example.sn';
        $phonePrefix = $this->faker->randomElement(['70', '75', '76', '77', '78']);

        return [
            'student_id' => Student::factory(),
            'full_name' => $fullName,
            'relationship' => $this->faker->randomElement(['FATHER', 'MOTHER', 'GUARDIAN']),
            'phone' => $phonePrefix.sprintf('%07d', rand(0, 9999999)),
            'email' => $email,
            'address' => $this->faker->randomElement($cities).', Senegal',
            'occupation' => $this->faker->randomElement($occupations),
        ];
    }

    /**
     * Indicate that the guardian is a father.
     */
    public function father(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => 'FATHER',
        ]);
    }

    /**
     * Indicate that the guardian is a mother.
     */
    public function mother(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => 'MOTHER',
        ]);
    }

    /**
     * Indicate that the guardian is a guardian.
     */
    public function guardian(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship' => 'GUARDIAN',
        ]);
    }
}

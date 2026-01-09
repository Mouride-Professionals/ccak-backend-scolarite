<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\GeneratedDocument;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GeneratedDocument> */
class GeneratedDocumentFactory extends Factory
{
    protected $model = GeneratedDocument::class;

    public function definition(): array
    {
        static $sequence = 1;
        $types = GeneratedDocument::getTypes();
        $type = $types[array_rand($types)];
        $year = date('Y');
        $number = str_pad((string) $sequence++, 5, '0', STR_PAD_LEFT);
        $documentNumber = 'UCAK-' . $year . '-' . $number;

        return [
            'id' => $this->faker->uuid(),
            'student_id' => Student::factory(),
            'type' => $type,
            'document_number' => $documentNumber,
            'file_path' => 'generated/' . strtolower($type) . '/' . $documentNumber . '.pdf',
            'generated_by' => User::factory(),
            'metadata' => [],
            'generated_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'issued_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'status' => GeneratedDocument::getStatuses()[array_rand(GeneratedDocument::getStatuses())],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

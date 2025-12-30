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
        $student = Student::factory()->create();

        return [
            'id' => $this->faker->uuid(),
            'student_id' => $student->id,
            'type' => $this->faker->randomElement([
                'TRANSCRIPT',
                'CERTIFICATE',
                'ATTESTATION',
                'ID_CARD',
                'DIPLOMA',
            ]),
            'document_number' => $this->faker->sentence(),
            'file_path' => $this->faker->filePath(),
            'generated_by' => User::factory(),
            'metadata' => [],
            'generated_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'issued_at' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'status' => $this->faker->randomElement(['DRAFT', 'ISSUED', 'REVOKED']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

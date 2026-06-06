<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 1;
        $type = $this->faker->randomElement(DocumentType::values());
        $baseNames = [
            DocumentType::CNI->value => 'carte_identite',
            DocumentType::BIRTH_CERT->value => 'acte_naissance',
            DocumentType::BAC_DIPLOMA->value => 'diplome_bac',
            DocumentType::TRANSCRIPT->value => 'releve_notes',
            DocumentType::PHOTO->value => 'photo_identite',
            DocumentType::MEDICAL->value => 'certificat_medical',
            DocumentType::ATTESTATION->value => 'attestation',
        ];
        $baseName = $baseNames[$type] ?? 'document';
        $number = str_pad((string) $sequence++, 4, '0', STR_PAD_LEFT);
        $fileName = $baseName.'_'.$number.'.pdf';

        return [
            'student_id' => Student::factory(),
            'type' => $type,
            'file_path' => 'documents/'.strtolower($type).'/'.$fileName,
            'file_name' => $fileName,
            'status' => DocumentStatus::PENDING->value,
            'uploaded_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the document is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PENDING',
        ]);
    }

    /**
     * Indicate that the document is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'APPROVED',
            'reviewed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the document is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'REJECTED',
            'reviewed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}

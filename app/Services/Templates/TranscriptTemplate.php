<?php

namespace App\Services\Templates;

use App\Contracts\Templates\DocumentTemplateInterface;

class TranscriptTemplate implements DocumentTemplateInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'Bulletin de notes';
    }

    /**
     * {@inheritDoc}
     */
    public function getView(): string
    {
        return 'pdf.transcript';
    }

    /**
     * {@inheritDoc}
     */
    /** @return array<string, mixed> */
    public function getRequiredData(): array
    {
        return [
            'student_name',
            'student_id',
            'courses' => [
                'course_name',
                'grade',
                'credits',
            ],
            'issue_date',
        ];
    }

    /**
     * {@inheritDoc}
     */
    /** @param array<string, mixed> $data */
    public function validateData(array $data): bool
    {
        $requiredFields = $this->getRequiredData();

        foreach ($requiredFields as $key => $field) {
            if (is_array($field)) {
                if (! isset($data[$key]) || ! is_array($data[$key])) {
                    return false;
                }
                foreach ($data[$key] as $item) {
                    foreach ($field as $subField) {
                        if (! isset($item[$subField])) {
                            return false;
                        }
                    }
                }
            } else {
                if (! isset($data[$field])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * Calcule :
     * - la moyenne pondérée (clé `average`)
     * - le total de crédits (clé `total_credits`)
     */
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function processData(array $data): array
    {
        $courses = isset($data['courses']) && is_array($data['courses']) ? $data['courses'] : [];
        $average = $this->getAverage($courses);
        $totalCredits = $this->getTotalCredits($courses);

        $processed = $data;
        $processed['average'] = $average;
        $processed['total_credits'] = $totalCredits;

        return $processed;
    }

    /**
     * {@inheritDoc}
     */
    public function getStyles(): string
    {
        return '';
    }

    /** @param array<int, array<string, mixed>> $courses */
    private function getAverage(array $courses): float
    {
        $totalCredits = $this->getTotalCredits($courses);
        $weightedSum = 0.0;

        foreach ($courses as $course) {
            $grade = isset($course['grade']) ? floatval($course['grade']) : 0.0;
            $credits = isset($course['credits']) ? floatval($course['credits']) : 0.0;

            $weightedSum += $grade * $credits;
        }

        $average = $weightedSum / $totalCredits;

        return $totalCredits > 0 ? round($average, 2, PHP_ROUND_HALF_UP) : 0.0;
    }

    /** @param array<int, array<string, mixed>> $courses */
    private function getTotalCredits(array $courses): float
    {
        $totalCredits = 0.0;

        foreach ($courses as $course) {
            $credits = isset($course['credits']) ? floatval($course['credits']) : 0.0;
            $totalCredits += $credits;
        }

        return $totalCredits;
    }
}

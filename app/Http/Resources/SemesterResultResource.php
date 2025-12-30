<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SemesterResult
 */
class SemesterResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'student_id' => $this->student_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
            'total_credits_enrolled' => $this->total_credits_enrolled,
            'total_credits_earned' => $this->total_credits_earned,
            'semester_average' => $this->semester_average,
            'semester_gpa' => $this->semester_gpa,
            'decision' => $this->decision,
            'calculated_by' => $this->calculated_by,
            'calculated_at' => $this->calculated_at,
        ];
    }
}

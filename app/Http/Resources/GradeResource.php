<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'course_enrollment_id' => $this->course_enrollment_id,
            'student_id' => $this->student_id,
            'course_id' => $this->course_id,
            'type' => $this->type,
            'score' => $this->score,
            'max_score' => $this->max_score,
            'weight' => $this->weight,
            'entered_by' => $this->entered_by,
            'status' => $this->status,
            'entered_at' => $this->entered_at,
            'validated_at' => $this->validated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'course_enrollment_id' => $this->course_enrollment_id,
            'student_id' => $this->student_id,
            'course_id' => $this->course_id,
            'assessment_id' => $this->assessment_id,
            'assessment' => $this->when($this->relationLoaded('assessment') && $this->assessment, fn () => [
                'id'    => $this->assessment->id,
                'title' => $this->assessment->title,
                'type'  => $this->assessment->type?->value,
                'date'  => $this->assessment->date?->toDateString(),
            ]),
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

<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'faculty_member_id' => $this->faculty_member_id,
            'academic_year_id' => $this->academic_year_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'question_template' => $this->question_template,
            'is_published' => $this->is_published,
            'response_deadline' => $this->response_deadline,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

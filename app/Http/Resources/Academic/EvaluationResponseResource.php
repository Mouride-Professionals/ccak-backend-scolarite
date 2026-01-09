<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResponseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'evaluation_id' => $this->evaluation_id,
            'student_id' => $this->student_id,
            'responses' => $this->responses,
            'rating_scores' => $this->rating_scores,
            'comments' => $this->comments,
            'is_anonymous' => $this->is_anonymous,
            'submitted_at' => $this->submitted_at,
        ];
    }
}

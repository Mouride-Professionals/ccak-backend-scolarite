<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'schedule_id' => $this->schedule_id,
            'faculty_member_id' => $this->faculty_member_id,
            'session_date' => $this->session_date,
            'topics' => $this->topics,
            'chapters' => $this->chapters,
            'objectives' => $this->objectives,
            'notes' => $this->notes,
            'signed_at' => $this->signed_at,
        ];
    }
}

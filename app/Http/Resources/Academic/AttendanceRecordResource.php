<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'course_log_id' => $this->course_log_id,
            'student_id' => $this->student_id,
            'status' => $this->status,
            'marked_at' => $this->marked_at,
            'notes' => $this->notes,
            'absence_count' => $this->absence_count,
            'is_dispensed' => $this->is_dispensed,
        ];
    }
}

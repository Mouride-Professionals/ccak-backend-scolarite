<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseEnrollmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'student_id' => $this->student_id,
            'course_id' => $this->course_id,
            'enrollment_id' => $this->enrollment_id,
            'course_id' => $this->course_id,
            'academic_year_id' => $this->academic_year_id,
            'semester' => $this->semester,
            'status' => $this->status,
            'enrollment_date' => $this->enrollment_date,
            'drop_date' => $this->drop_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

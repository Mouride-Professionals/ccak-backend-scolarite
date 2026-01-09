<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class AcademicProgramResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'duration_semesters' => $this->duration_semesters,
            'total_credits_required' => $this->total_credits_required,
            'is_active' => $this->is_active,
            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id,
                    'code' => $this->department->code,
                    'name' => $this->department->name,
                ];
            }),
            'course_units' => CourseUnitResource::collection($this->whenLoaded('courseUnits')),
        ];
    }
}

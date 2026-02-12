<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseUnitResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'academic_program_id' => $this->academic_program_id,
            'code' => $this->code,
            'name' => $this->name,
            'semester_number' => $this->semester_number,
            'credits' => $this->credits,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'academic_program' => $this->whenLoaded('academicProgram', function () {
                return [
                    'id' => $this->academicProgram->id,
                    'name' => $this->academicProgram->name,
                    'level' => $this->academicProgram->level,
                ];
            }),
            'courses' => CourseResource::collection($this->whenLoaded('courses')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

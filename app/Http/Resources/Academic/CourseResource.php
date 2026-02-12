<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'course_unit_id' => $this->course_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'credits' => $this->credits,
            'hours_lecture' => $this->hours_lecture,
            'hours_td' => $this->hours_td,
            'hours_tp' => $this->hours_tp,
            'coefficient' => $this->coefficient,
            'prerequisites' => $this->prerequisites,
            'is_active' => $this->is_active,
            'course_unit' => $this->whenLoaded('courseUnit', function () {
                return [
                    'id' => $this->courseUnit->id,
                    'code' => $this->courseUnit->code,
                    'name' => $this->courseUnit->name,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

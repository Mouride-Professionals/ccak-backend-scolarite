<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class TeachingAssignmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'faculty_member_id' => $this->faculty_member_id,
            'course_id' => $this->course_id,
            'academic_year_id' => $this->academic_year_id,

            // Flat convenience fields (populated when relations are loaded)
            'faculty_name' => $this->whenLoaded('facultyMember', fn () => $this->facultyMember->full_name),
            'course_name' => $this->whenLoaded('course', fn () => $this->course->name),
            'course_code' => $this->whenLoaded('course', fn () => $this->course->code),
            'level_name' => $this->whenLoaded('course', fn () => $this->course->courseUnit?->academicProgram?->name),
            'academic_year_label' => $this->whenLoaded('academicYear', fn () => $this->academicYear->name),

            // Nested relations (for detail views)
            'faculty_member' => $this->whenLoaded('facultyMember', fn () => [
                'id' => $this->facultyMember->id,
                'full_name' => $this->facultyMember->full_name,
                'staff_number' => $this->facultyMember->staff_number,
            ]),
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course->id,
                'code' => $this->course->code,
                'name' => $this->course->name,
            ]),
            'academic_year' => $this->whenLoaded('academicYear', fn () => [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
            ]),

            'role' => $this->role,
            'hours_assigned' => $this->hours_assigned,
            'hourly_rate' => $this->hourly_rate,

            // Delivery fields
            'hours_cm' => $this->hours_cm,
            'hours_td' => $this->hours_td,
            'planned_start_date' => $this->planned_start_date?->toDateString(),
            'effective_start_date' => $this->effective_start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status?->value,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

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
            'faculty_member' => $this->whenLoaded('facultyMember', function () {
                return [
                    'id' => $this->facultyMember->id,
                    'full_name' => $this->facultyMember->full_name,
                    'staff_number' => $this->facultyMember->staff_number,
                ];
            }),
            'course' => $this->whenLoaded('course', function () {
                return [
                    'id' => $this->course->id,
                    'code' => $this->course->code,
                    'name' => $this->course->name,
                ];
            }),
            'academic_year' => $this->whenLoaded('academicYear', function () {
                return [
                    'id' => $this->academicYear->id,
                    'name' => $this->academicYear->name,
                ];
            }),
            'role' => $this->role,
            'hours_assigned' => $this->hours_assigned,
            'hourly_rate' => $this->hourly_rate,
            'created_at' => $this->created_at,
        ];
    }
}

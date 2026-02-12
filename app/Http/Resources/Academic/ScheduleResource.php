<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'course' => $this->whenLoaded('course', function () {
                return [
                    'id' => $this->course->id,
                    'code' => $this->course->code,
                    'name' => $this->course->name,
                ];
            }),
            'faculty_member' => $this->whenLoaded('facultyMember', function () {
                return [
                    'id' => $this->facultyMember->id,
                    'full_name' => $this->facultyMember->full_name,
                    'staff_number' => $this->facultyMember->staff_number,
                ];
            }),
            'room' => $this->whenLoaded('room', function () {
                return [
                    'id' => $this->room->id,
                    'room_number' => $this->room->room_number,
                    'name' => $this->room->name,
                ];
            }),
            'activity_type' => $this->whenLoaded('activityType', function () {
                return [
                    'id' => $this->activityType->id,
                    'code' => $this->activityType->code,
                    'name' => $this->activityType->name,
                    'color' => $this->activityType->color,
                ];
            }),
            'academic_year_id' => $this->academic_year_id,
            'semester_number' => $this->semester_number,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'starts_on' => $this->starts_on,
            'ends_on' => $this->ends_on,
            'recurrence_pattern' => $this->recurrence_pattern,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

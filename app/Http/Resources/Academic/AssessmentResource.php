<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'course_id'           => $this->course_id,
            'course'              => $this->when($this->relationLoaded('course') && $this->course, fn () => [
                'id'      => $this->course->id,
                'code'    => $this->course->code,
                'name'    => $this->course->name,
                'credits' => $this->course->credits,
            ]),
            'faculty_member_id'   => $this->faculty_member_id,
            'faculty_member'      => $this->when($this->relationLoaded('facultyMember') && $this->facultyMember, fn () => [
                'id'        => $this->facultyMember->id,
                'full_name' => $this->facultyMember->full_name,
                'rank'      => $this->facultyMember->rank,
            ]),
            'academic_year_id'    => $this->academic_year_id,
            'academic_year'       => $this->when($this->relationLoaded('academicYear') && $this->academicYear, fn () => [
                'id'   => $this->academicYear->id,
                'name' => $this->academicYear->name,
            ]),
            'title'               => $this->title,
            'type'                => $this->type?->value,
            'type_label'          => $this->type?->label(),
            'date'                => $this->date?->toDateString(),
            'start_time'          => $this->start_time,
            'duration_minutes'    => $this->duration_minutes,
            'room'                => $this->room,
            'coefficient'         => $this->coefficient,
            'is_grades_published' => $this->is_grades_published,
            'notes'               => $this->notes,
            'created_at'          => optional($this->created_at)->toISOString(),
            'updated_at'          => optional($this->updated_at)->toISOString(),
        ];
    }
}

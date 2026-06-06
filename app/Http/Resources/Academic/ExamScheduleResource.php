<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'exam_session_id' => $this->exam_session_id,
            'course_id' => $this->course_id,
            'course' => $this->when($this->relationLoaded('course') && $this->course, fn () => [
                'id' => $this->course->id,
                'code' => $this->course->code,
                'name' => $this->course->name,
                'credits' => $this->course->credits,
            ]),
            'room_id' => $this->room_id,
            'room' => $this->when($this->relationLoaded('room') && $this->room, fn () => [
                'id' => $this->room->id,
                'name' => $this->room->name,
                'room_number' => $this->room->room_number,
                'capacity' => $this->room->capacity,
            ]),
            'date' => $this->date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'notes' => $this->notes,
            'invigilators' => $this->when($this->relationLoaded('invigilators'), fn () => $this->invigilators->map(fn ($m) => [
                'id' => $m->id,
                'full_name' => $m->full_name,
                'rank' => $m->rank,
            ])
            ),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

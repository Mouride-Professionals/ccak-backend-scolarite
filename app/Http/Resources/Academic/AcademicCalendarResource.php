<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class AcademicCalendarResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'academic_year_id' => $this->academic_year_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'working_days' => $this->working_days,
            'weekend_days' => $this->weekend_days,
            'hour_slots' => $this->hour_slots,
            'break_slots' => $this->break_slots,
        ];
    }
}

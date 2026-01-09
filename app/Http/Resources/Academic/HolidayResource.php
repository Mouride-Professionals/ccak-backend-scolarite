<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class HolidayResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'academic_year_id' => $this->academic_year_id,
            'date' => $this->date,
            'name' => $this->name,
            'type' => $this->type,
            'is_recurring' => $this->is_recurring,
        ];
    }
}

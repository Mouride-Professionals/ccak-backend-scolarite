<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'room_number' => $this->room_number,
            'name' => $this->name,
            'building' => $this->building,
            'capacity' => $this->capacity,
            'type' => $this->type,
            'equipment' => $this->equipment,
            'is_available' => $this->is_available,
        ];
    }
}

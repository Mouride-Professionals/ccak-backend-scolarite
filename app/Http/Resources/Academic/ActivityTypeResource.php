<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'duration_minutes' => $this->duration_minutes,
            'color' => $this->color,
            'is_active' => $this->is_active,
        ];
    }
}

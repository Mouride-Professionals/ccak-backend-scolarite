<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class LevelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'code'               => $this->code,
            'type'               => $this->type,
            'degree_cycle_id'    => $this->degree_cycle_id,
            'numero'             => $this->numero,
            'duration_semesters' => $this->duration_semesters,
            'is_active'          => $this->is_active,
        ];
    }
}

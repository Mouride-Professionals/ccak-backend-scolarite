<?php

namespace App\Http\Resources\Academic;

use App\Http\Resources\Academic\DepartmentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FacultyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'dean_id' => $this->dean_id,
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'dean' => $this->whenLoaded('dean', function () {
                return [
                    'id' => $this->dean->id,
                    'email' => $this->dean->email,
                ];
            }),
            'departments' => DepartmentResource::collection($this->whenLoaded('departments')),
        ];
    }
}

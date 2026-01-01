<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class FacultyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
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

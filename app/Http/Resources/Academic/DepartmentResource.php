<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'faculty' => $this->whenLoaded('faculty', function () {
                return [
                    'id' => $this->faculty->id,
                    'code' => $this->faculty->code,
                    'name' => $this->faculty->name,
                ];
            }),
            'head' => $this->whenLoaded('head', function () {
                return [
                    'id' => $this->head->id,
                    'email' => $this->head->email,
                ];
            }),
            'programs' => AcademicProgramResource::collection($this->whenLoaded('programs')),
        ];
    }
}

<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class AcademicProgramResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}

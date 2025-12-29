<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_number' => $this->student_number,
            'full_name' => $this->full_name,
        ];
    }
}

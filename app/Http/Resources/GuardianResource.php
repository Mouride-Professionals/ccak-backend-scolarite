<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Guardian
 */
class GuardianResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'full_name' => $this->full_name,
            'relationship' => $this->relationship,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'occupation' => $this->occupation,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

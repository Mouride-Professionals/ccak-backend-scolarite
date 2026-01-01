<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class FacultyMemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'staff_number' => $this->staff_number,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'rank' => $this->rank,
            'contract_type' => $this->contract_type,
            'hire_date' => $this->hire_date,
            'is_active' => $this->is_active,
            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id,
                    'code' => $this->department->code,
                    'name' => $this->department->name,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }
}

<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FacultyContractResource extends JsonResource
{
    public function toArray($request): array
    {
        $endDate = $this->end_date;
        $isExpiring = $endDate ? $endDate->diffInDays(now()) <= 30 && $endDate->isFuture() : false;

        return [
            'id' => $this->id,
            'faculty_member_id' => $this->faculty_member_id,
            'contract_type' => $this->contract_type,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'salary' => $this->salary,
            'terms' => $this->terms,
            'status' => $this->status,
            'is_current' => $this->is_current,
            'file_path' => $this->file_path,
            'file_url' => $this->file_path ? Storage::disk('documents')->url($this->file_path) : null,
            'file_name' => $this->file_name,
            'is_expiring' => $isExpiring,
            'created_at' => $this->created_at,
        ];
    }
}

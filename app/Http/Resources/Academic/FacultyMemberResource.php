<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Academic\TeachingAssignmentResource;
use App\Http\Resources\Academic\FacultyDocumentResource;
use App\Http\Resources\Academic\FacultyContractResource;

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
            'assignments' => $this->whenLoaded('teachingAssignments', function () {
                return TeachingAssignmentResource::collection($this->teachingAssignments);
            }),
            'documents' => $this->whenLoaded('documents', function () {
                return FacultyDocumentResource::collection($this->documents);
            }),
            'contracts' => $this->whenLoaded('contracts', function () {
                return FacultyContractResource::collection($this->contracts);
            }),
        ];
    }
}

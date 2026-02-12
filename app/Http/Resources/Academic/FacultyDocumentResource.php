<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FacultyDocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'faculty_member_id' => $this->faculty_member_id,
            'type' => $this->type,
            'status' => $this->status,
            'file_path' => $this->file_path,
            'file_url' => $this->file_path ? Storage::disk('documents')->url($this->file_path) : null,
            'file_name' => $this->file_name,
            'notes' => $this->notes,
            'reviewed_at' => $this->reviewed_at,
            'reviewed_by' => $this->whenLoaded('reviewer', function () {
                return [
                    'id' => $this->reviewer->id,
                    'user_id' => $this->reviewer->user_id,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

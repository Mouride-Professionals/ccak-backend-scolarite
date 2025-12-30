<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'type' => $this->type?->value ?? $this->type,
            'status' => $this->status?->value ?? $this->status,
            'reviewed_by' => $this->reviewed_by,
            'file_path' => $this->file_path,
            'file_name' => $this->file_name,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'uploaded_at' => $this->uploaded_at,
            'reviewed_at' => $this->reviewed_at,
        ];
    }
}

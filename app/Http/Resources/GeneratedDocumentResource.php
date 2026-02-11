<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GeneratedDocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'type' => $this->type,
            'document_number' => $this->document_number,
            'file_path' => $this->file_path,
            'media_id' => $this->media_id,
            'generated_by' => $this->generated_by,
            'metadata' => $this->metadata,
            'generated_at' => $this->generated_at,
            'issued_at' => $this->issued_at,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Http\Resources\Academic;

use App\Http\Resources\Academic\DeliberationSessionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliberationResultResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'deliberation_session_id' => $this->deliberation_session_id,
            'deliberation_session' => new DeliberationSessionResource($this->whenLoaded('deliberationSession')),
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student'),
            'decision' => $this->decision,
            'jury_remarks' => $this->jury_remarks,
            'is_with_honors' => (bool) $this->is_with_honors,
            'honor_level' => $this->honor_level,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

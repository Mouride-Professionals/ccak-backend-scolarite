<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\GuardianResource;
use App\Http\Resources\UserResource;

/**
 * @mixin \App\Models\Student
 */
class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_number' => $this->student_number,
            'full_name' => $this->full_name,
            'user_id' => $this->user_id,
            'keycloak_user_id' => $this->keycloak_user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'date_of_birth' => $this->date_of_birth,
            'place_of_birth' => $this->place_of_birth,
            'nationality' => $this->nationality,
            'phone' => $this->phone,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'address' => $this->address,
            'photo_url' => $this->photo_url,
            'status' => $this->status,
            'guardians' => $this->whenLoaded('guardians', function () {
                return GuardianResource::collection($this->guardians);
            }),
            'documents' => $this->whenLoaded('documents', function () {
                return DocumentResource::collection($this->documents);
            }),
        ];
    }
}

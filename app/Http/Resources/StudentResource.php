<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
            'student_number' => $this->student_number,
            'full_name' => $this->full_name,
            'user_id' => $this->user_id,
            'date_of_birth' => $this->date_of_birth,
            'place_of_birth' => $this->place_of_birth,
            'nationality' => $this->nationality,
            'phone' => $this->phone,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'address' => $this->address,
            'photo_url' => $this->photo_url,
            'status' => $this->status,
        ];
    }
}

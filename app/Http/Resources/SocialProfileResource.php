<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SocialProfile
 */
class SocialProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'profilable_type' => $this->profilable_type,
            'profilable_id' => $this->profilable_id,
            'family_status' => $this->family_status,
            'number_of_children' => $this->number_of_children,
            'is_employed' => $this->is_employed,
            'socio_professional_category' => $this->socio_professional_category,
            'student_regime' => $this->student_regime,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\PriorDiploma
 */
class PriorDiplomaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'year' => $this->year,
            'mention' => $this->mention,
            'institution' => $this->institution,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

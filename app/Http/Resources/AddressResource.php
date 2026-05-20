<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Address
 */
class AddressResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'addressable_type' => $this->addressable_type,
            'addressable_id'   => $this->addressable_id,
            'type'             => $this->type,
            'street'           => $this->street,
            'city'             => $this->city,
            'region'           => $this->region,
            'department'       => $this->department,
            'country'          => $this->country,
            'is_primary'       => $this->is_primary,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\AddressResource;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\GuardianResource;
use App\Http\Resources\PriorDiplomaResource;
use App\Http\Resources\SocialProfileResource;
use App\Http\Resources\StudentBacInfoResource;
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
            'id'                     => $this->id,
            'student_number'         => $this->student_number,
            'first_name'             => $this->first_name,
            'last_name'              => $this->last_name,
            'full_name'              => $this->full_name,
            'gender'                 => $this->gender,
            'date_of_birth'          => $this->date_of_birth,
            'place_of_birth'         => $this->place_of_birth,
            'nationality'            => $this->nationality,
            'type_of_id'             => $this->type_of_id,
            'id_details'             => $this->id_details,
            'ine'                    => $this->ine,
            'email'                  => $this->email,
            'email_university'       => $this->email_university,
            'phone'                  => $this->phone,
            'phone_2'                => $this->phone_2,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone'=> $this->emergency_contact_phone,
            'address'                => $this->address,
            'photo_url'              => $this->photo_url,
            'status'                 => $this->status,
            'provenance'             => $this->provenance,
            'registration_number'    => $this->registration_number,
            'synced_from'            => $this->synced_from,
            'last_synced_at'         => $this->last_synced_at,
            'user_id'                => $this->user_id,
            'keycloak_user_id'       => $this->keycloak_user_id,
            'user'           => new UserResource($this->whenLoaded('user')),
            'bac_info'       => $this->whenLoaded('bacInfo', fn () => $this->bacInfo ? new StudentBacInfoResource($this->bacInfo) : null),
            'addresses'      => $this->whenLoaded('addresses', fn () => AddressResource::collection($this->addresses)),
            'social_profile' => $this->whenLoaded('socialProfile', fn () => $this->socialProfile ? new SocialProfileResource($this->socialProfile) : null),
            'guardians'      => $this->whenLoaded('guardians', fn () => GuardianResource::collection($this->guardians)),
            'prior_diplomas' => $this->whenLoaded('priorDiplomas', fn () => PriorDiplomaResource::collection($this->priorDiplomas)),
            'documents'      => $this->whenLoaded('documents', fn () => DocumentResource::collection($this->documents)),
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }
}

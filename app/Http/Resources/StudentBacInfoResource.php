<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\StudentBacInfo
 */
class StudentBacInfoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'serie' => $this->serie,
            'year_of_bac' => $this->year_of_bac,
            'bac_result_id' => $this->bac_result_id,
            'first_round_average' => $this->first_round_average,
            'second_round_average' => $this->second_round_average,
            'bac_mention' => $this->bac_mention,
            'bac_institution' => $this->bac_institution,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

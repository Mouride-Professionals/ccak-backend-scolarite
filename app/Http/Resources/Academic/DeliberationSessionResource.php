<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliberationSessionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'academic_program_id' => $this->academic_program_id,
            'academic_program' => new AcademicProgramResource($this->whenLoaded('academicProgram')),
            'academic_year_id' => $this->academic_year_id,
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'semester' => $this->semester,
            'session_name' => $this->session_name,
            'session_date' => $this->session_date?->toDateString(),
            'status' => $this->status,
            'presided_by' => $this->presided_by,

            'president' => $this->when($this->relationLoaded('president') && $this->president, function () {
                return [
                    'id' => $this->president->id,
                    'full_name' => $this->president->full_name,
                    'rank' => $this->president->rank,
                ];
            }),

            'jury_members' => $this->when($this->relationLoaded('juryMembers'), function () {
                return $this->juryMembers->map(fn ($m) => [
                    'id' => $m->id,
                    'full_name' => $m->full_name,
                    'rank' => $m->rank,
                ]);
            }),

            'academicProgram' => $this->whenLoaded('academicProgram'),
            'academicYear' => $this->whenLoaded('academicYear'),
            'results' => $this->whenLoaded('results'),

            'results' => $this->whenLoaded('results'),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'academic_program_id' => $this->academic_program_id,
            'academic_year_id' => $this->academic_year_id,
            'current_semester' => $this->current_semester,
            'status' => $this->status,
            'enrollment_date' => $this->enrollment_date,
            'registration_fee_paid' => $this->registration_fee_paid,
            'is_scholarship' => $this->is_scholarship,
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'student_number' => $this->student->student_number,
                    'full_name' => $this->student->full_name,
                ];
            }),
            'academic_program' => $this->whenLoaded('academicProgram', function () {
                return [
                    'id' => $this->academicProgram->id,
                    'name' => $this->academicProgram->name,
                    'level' => $this->academicProgram->level,
                ];
            }),
            'academic_year' => $this->whenLoaded('academicYear', function () {
                return [
                    'id' => $this->academicYear->id,
                    'name' => $this->academicYear->name,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

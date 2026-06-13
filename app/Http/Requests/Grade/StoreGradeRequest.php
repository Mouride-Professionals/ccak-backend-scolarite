<?php

declare(strict_types=1);

namespace App\Http\Requests\Grade;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreGradeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // Check if user is authenticated and has faculty role
        return $user && $user->hasAnyRole(['FACULTY']);
    }

    public function rules(): array
    {
        return [
            'course_enrollment_id' => 'required|string|exists:course_enrollments,id',
            'student_id' => 'required|string|exists:students,id',
            'course_id' => 'required|string|exists:courses,id',
            'assessment_id' => 'nullable|string|exists:assessments,id',
            'type' => 'required|string|in:CC,EXAM,TP,ORAL',
            'score' => 'required|numeric|min:0',
            'max_score' => 'required|numeric|min:0|gt:0',
            'weight' => 'required|numeric|min:0',
        ];
    }

    /**
     * Configure the validator instance
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->getData();

            // Validate score is within range (0 to max_score)
            if (isset($data['score'], $data['max_score']) && $data['score'] > $data['max_score']) {
                $validator->errors()->add(
                    'score',
                    "La note ne peut pas dépasser le score maximum de {$data['max_score']}."
                );
            }

            // Validate course enrollment consistency
            if (isset($data['course_enrollment_id'], $data['student_id'], $data['course_id'])) {
                $enrollment = \App\Models\CourseEnrollment::where('id', $data['course_enrollment_id'])
                    ->where('student_id', $data['student_id'])
                    ->where('course_id', $data['course_id'])
                    ->first();

                if (! $enrollment) {
                    $validator->errors()->add(
                        'course_enrollment_id',
                        'L\'inscription au cours ne correspond pas à l\'étudiant et au cours fournis.'
                    );
                }
            }

            // Validate no duplicate grade type for the same enrollment
            // Exception: multiple CC grades are allowed when each has a distinct assessment_id
            if (isset($data['course_enrollment_id'], $data['type'])) {
                $query = \App\Models\Grade::where('course_enrollment_id', $data['course_enrollment_id'])
                    ->where('type', $data['type']);

                if (! empty($data['assessment_id'])) {
                    $query->where('assessment_id', $data['assessment_id']);
                }

                if ($query->exists()) {
                    $validator->errors()->add(
                        'type',
                        "Une note de type {$data['type']} existe déjà pour cette inscription au cours."
                    );
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Le type de note doit être l\'un des suivants : CC (Contrôle Continu), EXAM, TP (Travaux Pratiques), ou ORAL.',
            'score.min' => 'La note doit être au minimum 0.',
            'max_score.gt' => 'Le score maximum doit être supérieur à 0.',
            'weight.min' => 'Le poids doit être au minimum 0.',
        ];
    }

    /**
     * Handle a failed authorization attempt
     */
    protected function failedAuthorization(): void
    {
        abort(403, 'Seuls les membres du corps enseignant sont autorisés à créer des notes.');
    }
}

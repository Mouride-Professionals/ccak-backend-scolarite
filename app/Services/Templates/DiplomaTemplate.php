<?php

namespace App\Services\Templates;

use App\Contracts\Templates\DocumentTemplateInterface;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\AcademicProgram;
use App\Models\Deliberation_Result;
use App\Enum\DocumentType;

class DiplomaTemplate implements DocumentTemplateInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'Diplôme universitaire';
    }

    /**
     * @inheritDoc
     */
    public function getView(): string
    {
        return 'pdf.diploma';
    }

    /**
     * @inheritDoc
     */
    public function getRequiredData(): array
    {
        return [
            'student' => 'required', // Student model instance
            'enrollment' => 'required', // Enrollment model instance
            'academic_program' => 'required', // AcademicProgram model instance
            'deliberation_result' => 'required', // Deliberation_Result model instance
            'signatures' => [
                'rector' => [
                    'name',
                    'title',
                ],
                'dean' => [
                    'name',
                    'title',
                ],
                'registrar' => [
                    'name',
                    'title',
                ],
                'department_head' => [
                    'name',
                    'title',
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateData(array $data): bool
    {
        $requiredFields = $this->getRequiredData();
        
        // Check all required model instances exist
        foreach (['student', 'enrollment', 'academic_program', 'deliberation_result'] as $modelKey) {
            if (!isset($data[$modelKey]) || !$this->validateModelInstance($modelKey, $data[$modelKey])) {
                return false;
            }
        }
        
        // Check student is graduated
        if ($data['student']->status->value !== 'GRADUATED') {
            return false;
        }
        
        // Check enrollment is completed
        if ($data['enrollment']->status->value !== 'COMPLETED') {
            return false;
        }
        
        // Check deliberation result is positive
        if (!in_array($data['deliberation_result']->decision->value, ['ADMITTED', 'ADMITTED_COMPENSATION'])) {
            return false;
        }
        
        // Validate signatures
        if (!$this->validateSignatures($data['signatures'] ?? [])) {
            return false;
        }
        
        return true;
    }

    /**
     * @inheritDoc
     */
    public function processData(array $data): array
    {
        /** @var Student $student */
        $student = $data['student'];
        
        /** @var Enrollment $enrollment */
        $enrollment = $data['enrollment'];
        
        /** @var AcademicProgram $academicProgram */
        $academicProgram = $data['academic_program'];
        
        /** @var Deliberation_Result $deliberationResult */
        $deliberationResult = $data['deliberation_result'];
        
        $processed = $data;
        
        // Add student information
        $processed['student_info'] = $this->prepareStudentInfo($student);
        
        // Add academic information
        $processed['academic_info'] = $this->prepareAcademicInfo($enrollment, $academicProgram);
        
        // Add deliberation information
        $processed['deliberation_info'] = $this->prepareDeliberationInfo($deliberationResult);
        
        // Format dates
        $processed['issue_date_formatted'] = $this->formatDate(now()->toDateString());
        $processed['completion_date_formatted'] = $this->formatDate($enrollment->enrollment_date);
        
        // Get GPA from semester results
        $processed['gpa'] = $this->getStudentGPA($student, $enrollment);
        $processed['gpa_formatted'] = number_format($processed['gpa'], 2, ',', ' ');
        
        // Determine honors level
        $processed['honors_level'] = $this->determineHonorsLevel(
            $processed['gpa'],
            $deliberationResult->is_with_honors ? $deliberationResult->honor_level->value : null
        );
        
        // Generate diploma title
        $processed['diploma_title'] = $this->generateDiplomaTitle($academicProgram);
        
        // Generate degree level in French
        $processed['degree_level_fr'] = $this->getFrenchDegreeLevel($academicProgram->level->value);
        
        // Prepare signatures
        $processed['signatures'] = $this->prepareSignatures($data['signatures'] ?? []);
        
        // Add seal and signature image paths
        $processed['university_seal'] = storage_path('app/branding/university_seal.png');
        $processed['official_stamp'] = storage_path('app/branding/official_stamp.png');
        
        // Add document validity info
        $processed['validity_info'] = $this->getValidityInfo($processed);
        
        // Add registration number based on student and program
        $processed['registration_number'] = $this->generateRegistrationNumber($student, $academicProgram);
        
        return $processed;
    }

    /**
     * @inheritDoc
     */
    public function getStyles(): string
    {
        return '';
    }

    /**
     * Validate model instance
     */
    private function validateModelInstance(string $type, $instance): bool
    {
        switch ($type) {
            case 'student':
                return $instance instanceof Student;
            case 'enrollment':
                return $instance instanceof Enrollment;
            case 'academic_program':
                return $instance instanceof AcademicProgram;
            case 'deliberation_result':
                return $instance instanceof Deliberation_Result;
            default:
                return false;
        }
    }

    /**
     * Validate signatures data
     */
    private function validateSignatures(array $signatures): bool
    {
        $requiredSignatures = ['rector', 'dean', 'registrar', 'department_head'];
        
        foreach ($requiredSignatures as $type) {
            if (!isset($signatures[$type])) {
                return false;
            }
            
            if (!isset($signatures[$type]['name']) || empty(trim($signatures[$type]['name']))) {
                return false;
            }
            
            if (!isset($signatures[$type]['title']) || empty(trim($signatures[$type]['title']))) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Prepare student information for the diploma
     */
    private function prepareStudentInfo(Student $student): array
    {
        return [
            'id' => $student->id,
            'user_id' => $student->user_id,
            'full_name' => $student->full_name,
            'student_number' => $student->student_number,
            'gender' => $student->gender->label(),
            'date_of_birth' => $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : 'N/A',
            'place_of_birth' => $student->place_of_birth,
            'nationality' => $student->nationality,
            'phone' => $student->phone,
            'address' => $student->address,
            'photo_url' => $student->photo_url_full,
            'status' => $student->status->label(),
            'age' => $student->date_of_birth ? $student->date_of_birth->age : null,
        ];
    }

    /**
     * Prepare academic information
     */
    private function prepareAcademicInfo(Enrollment $enrollment, AcademicProgram $academicProgram): array
    {
        return [
            'program_name' => $academicProgram->name,
            'program_code' => $academicProgram->department->code . '-' . $academicProgram->level->value,
            'faculty' => $academicProgram->department->faculty->name,
            'department' => $academicProgram->department->name,
            'level' => $academicProgram->level->value,
            'duration_semesters' => $academicProgram->duration_semesters,
            'total_credits_required' => $academicProgram->total_credits_required,
            'enrollment_date' => $enrollment->enrollment_date->format('d/m/Y'),
            'academic_year' => $enrollment->academicYear->name,
            'current_semester' => $enrollment->current_semester,
            'is_scholarship' => $enrollment->is_scholarship,
        ];
    }

    /**
     * Prepare deliberation information
     */
    private function prepareDeliberationInfo(Deliberation_Result $deliberationResult): array
    {
        return [
            'decision' => $deliberationResult->decision->label(),
            'honor_level' => $deliberationResult->honor_level?->label(),
            'is_with_honors' => $deliberationResult->is_with_honors,
            'jury_remarks' => $deliberationResult->jury_remarks,
            'deliberation_date' => $deliberationResult->created_at->format('d/m/Y'),
            'session_name' => $deliberationResult->deliberationSession->session_name ?? 'N/A',
        ];
    }

    /**
     * Get student GPA from semester results
     */
    private function getStudentGPA(Student $student, Enrollment $enrollment): float
    {
        // Get the latest semester result for this enrollment
        $semesterResult = $student->semesterResults()
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->orderBy('semester', 'desc')
            ->first();
        
        return $semesterResult ? $semesterResult->semester_gpa : 0.0;
    }

    /**
     * Determine honors level based on GPA and deliberation result
     */
    private function determineHonorsLevel(float $gpa, ?string $honorLevel = null): ?string
    {
        // If honor level from deliberation, use it
        if ($honorLevel) {
            $honorMap = [
                'PASSABLE' => 'PASSABLE',
                'ASSEZ_BIEN' => 'ASSEZ BIEN',
                'BIEN' => 'BIEN',
                'TRES_BIEN' => 'TRÈS BIEN',
            ];
            
            return $honorMap[$honorLevel] ?? null;
        }
        
        // Otherwise determine from GPA
        if ($gpa >= 3.9) {
            return 'AVEC LA PLUS HAUTE DISTINCTION';
        } elseif ($gpa >= 3.7) {
            return 'AVEC GRANDE DISTINCTION';
        } elseif ($gpa >= 3.5) {
            return 'AVEC DISTINCTION';
        }
        
        return null;
    }

    /**
     * Generate diploma title based on academic program
     */
    private function generateDiplomaTitle(AcademicProgram $academicProgram): string
    {
        $level = $this->getFrenchDegreeLevel($academicProgram->level->value);
        $field = $academicProgram->department->name;
        
        return "DIPLÔME DE $level EN $field";
    }

    /**
     * Get French translation for degree level
     */
    private function getFrenchDegreeLevel(string $degreeType): string
    {
        $degreeMap = [
            'LICENCE' => 'LICENCE',
            'MASTER' => 'MASTER',
            'DOCTORAT' => 'DOCTORAT',
        ];
        
        return $degreeMap[$degreeType] ?? strtoupper($degreeType);
    }

    /**
     * Prepare signatures
     */
    private function prepareSignatures(array $signatures): array
    {           
        $defaultSignatures = [
            'rector' => [
                'name' => 'Dr. Lamine Gueye',
                'title' => 'Recteur de l\'Université',
                'signature_image' => storage_path('app/branding/signature_rector.png'),
            ],
            'dean' => [
                'name' => 'Masseck Fall',
                'title' => 'Doyen de la Faculté',
                'signature_image' => storage_path('app/branding/signature_dean.png'),
            ],
            'registrar' => [
                'name' => 'Khadim Mbacke',
                'title' => 'Directeur des Services Académiques',
                'signature_image' => storage_path('app/branding/signature_registrar.png'),
            ],
            'department_head' => [
                'name' => 'Abdou Diop',
                'title' => 'Chef de Département',
                'signature_image' => storage_path('app/branding/signature_department_head.png'),
            ],
        ];
        
        return array_merge($defaultSignatures, $signatures);
    }

    /**
     * Generate registration number
     */
    private function generateRegistrationNumber(Student $student, AcademicProgram $academicProgram): string
    {
        $programCode = $academicProgram->department->code;
        $year = substr($student->student_number, 4, 4);
        $sequence = substr($student->student_number, -4);
        
        return "UCAK-{$programCode}-{$year}-{$sequence}";
    }

    /**
     * Get validity information
     */
    private function getValidityInfo(array $data): array
    {
        return [
            'valid_from' => now()->format('Y-m-d'),
            'valid_until' => now()->addYears(10)->format('Y-m-d'),
            'verification_url' => route('documents.verify', ['number' => $data['document_number'] ?? '']),
            'authority' => 'Ministère de l\'Enseignement Supérieur et de la Recherche',
            'reference_number' => $this->generateReferenceNumber($data),
        ];
    }

    /**
     * Generate reference number for ministry
     */
    private function generateReferenceNumber(array $data): string
    {
        $date = now()->format('Ymd');
        $studentNumber = $data['student_info']['student_number'] ?? '000000';
        $programCode = $data['academic_info']['program_code'] ?? 'XXX';
        
        return "MESR-{$programCode}-{$studentNumber}-{$date}";
    }

    /**
     * Check if a date is valid
     */
    private function isValidDate($date): bool
    {
        if (empty($date)) {
            return false;
        }
        
        try {
            new \DateTime($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Format date in French style
     */
    private function formatDate($date): string
    {
        try {
            $dateTime = new \DateTime($date);
            
            // French month names
            $months = [
                1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
                5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
                9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
            ];
            
            $day = $dateTime->format('j');
            $month = $months[(int)$dateTime->format('n')];
            $year = $dateTime->format('Y');
            
            return "le {$day} {$month} {$year}";
        } catch (\Exception $e) {
            return $date;
        }
    }
}
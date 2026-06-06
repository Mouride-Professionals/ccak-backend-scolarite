<?php

namespace App\Services\Student;

use App\Models\Student;
use Illuminate\Support\Facades\DB;

class StudentNumberService
{
    /**
     * Génère un numéro d'étudiant unique selon le format UCAK{ANNEE}{NUMERO_SEQUENTIEL}.
     * Exemple: UCAK2024001
     */
    public function generate(): string
    {
        return DB::transaction(function (): string {
            $year = date('Y');
            $prefix = "UCAK{$year}";

            $lastStudent = Student::query()
                ->where('student_number', 'like', "{$prefix}%")
                ->orderBy('student_number', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastStudent) {
                $lastNumber = (int) substr($lastStudent->student_number, -3);
                $newNumber = str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '001';
            }

            return "{$prefix}{$newNumber}";
        }, 3);
    }
}

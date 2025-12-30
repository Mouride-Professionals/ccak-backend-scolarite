<?php

namespace App\Services\Student;

use App\Models\Student;

class StudentNumberService
{
    /**
     * Génère un numéro d'étudiant unique selon le format UCAK{ANNEE}{NUMERO_SEQUENTIEL}.
     * Exemple: UCAK2024001
     *
     * @return string
     */
    public function generate(): string
    {
        $year = date('Y');
        $prefix = "UCAK{$year}";

        // Trouver le dernier numéro d'étudiant pour l'année en cours
        $lastStudent = Student::where('student_number', 'like', "{$prefix}%")
            ->orderBy('student_number', 'desc')
            ->first();

        if ($lastStudent) {
            // Extraire le numéro séquentiel (les 3 derniers caractères)
            $lastNumber = (int) substr($lastStudent->student_number, -3);
            $newNumber = str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
        } else {
            // Premier étudiant de l'année
            $newNumber = '001';
        }

        return "{$prefix}{$newNumber}";
    }
}

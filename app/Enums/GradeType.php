<?php

namespace App\Enums;

enum GradeType: string
{
    case CC = 'CC';       // Contrôle Continu
    case EXAM = 'EXAM';   // Examen Final
    case TP = 'TP';       // Travaux Pratiques
    case ORAL = 'ORAL';   // Examen Oral

    /**
     * Get the label for the grade type.
     */
    public function label(): string
    {
        return match($this) {
            self::CC => 'Contrôle Continu',
            self::EXAM => 'Examen Final',
            self::TP => 'Travaux Pratiques',
            self::ORAL => 'Examen Oral',
        };
    }
}

<?php

namespace App\Enums;

enum GradeStatus: string
{
    case DRAFT = 'DRAFT';           // Brouillon
    case SUBMITTED = 'SUBMITTED';   // Soumis pour validation
    case VALIDATED = 'VALIDATED';   // Validé par l'admin
    case PUBLISHED = 'PUBLISHED';   // Publié aux étudiants

    /**
     * Get the label for the grade status.
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Brouillon',
            self::SUBMITTED => 'Soumis',
            self::VALIDATED => 'Validé',
            self::PUBLISHED => 'Publié',
        };
    }
}

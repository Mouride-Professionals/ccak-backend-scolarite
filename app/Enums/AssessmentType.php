<?php

namespace App\Enums;

enum AssessmentType: string
{
    case WRITTEN      = 'WRITTEN';      // Écrit
    case ORAL         = 'ORAL';         // Oral
    case LAB          = 'LAB';          // Travaux Pratiques
    case QCM          = 'QCM';          // QCM
    case PRESENTATION = 'PRESENTATION'; // Exposé / Soutenance

    public function label(): string
    {
        return match ($this) {
            self::WRITTEN      => 'Écrit',
            self::ORAL         => 'Oral',
            self::LAB          => 'Travaux Pratiques',
            self::QCM          => 'QCM',
            self::PRESENTATION => 'Exposé / Soutenance',
        };
    }
}

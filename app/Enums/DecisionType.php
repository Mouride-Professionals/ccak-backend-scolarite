<?php

namespace App\Enums;

enum DecisionType: string
{
    case VALIDATED = 'VALIDATED';           // Semestre validé
    case COMPENSATION = 'COMPENSATION';     // Validé par compensation
    case FAILED = 'FAILED';                 // Échec
    case RESIT_REQUIRED = 'RESIT_REQUIRED'; // Rattrapage requis
}

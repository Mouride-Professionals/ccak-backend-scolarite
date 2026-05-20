<?php

namespace App\Enums;

enum TeachingDeliveryStatus: string
{
    case COMPLETED = 'COMPLETED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case LATE = 'LATE';
    case NOT_STARTED = 'NOT_STARTED';

    public function label(): string
    {
        return match($this) {
            self::COMPLETED    => 'Achevé',
            self::IN_PROGRESS  => 'En cours',
            self::LATE         => 'En retard',
            self::NOT_STARTED  => 'Non commencé',
        };
    }
}

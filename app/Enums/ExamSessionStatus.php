<?php

namespace App\Enums;

enum ExamSessionStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case CLOSED = 'CLOSED';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Brouillon',
            self::PUBLISHED => 'Publié',
            self::CLOSED => 'Clôturé',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

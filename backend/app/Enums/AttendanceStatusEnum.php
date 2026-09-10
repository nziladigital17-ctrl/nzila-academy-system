<?php

namespace App\Enums;

enum AttendanceStatusEnum: string
{
    case PRESENT           = 'P';  // Presente
    case ABSENT_UNJUSTIFIED = 'F'; // Falta não justificada
    case ABSENT_JUSTIFIED  = 'J';  // Falta justificada

    public function displayName(): string
    {
        return match ($this) {
            self::PRESENT            => 'Presente',
            self::ABSENT_UNJUSTIFIED => 'Falta não justificada',
            self::ABSENT_JUSTIFIED   => 'Falta justificada',
        };
    }

    public function isAbsence(): bool
    {
        return $this !== self::PRESENT;
    }

    public function isUnjustifiedAbsence(): bool
    {
        return $this === self::ABSENT_UNJUSTIFIED;
    }

    public function isJustifiedAbsence(): bool
    {
        return $this === self::ABSENT_JUSTIFIED;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

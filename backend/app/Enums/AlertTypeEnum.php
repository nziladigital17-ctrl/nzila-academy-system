<?php

namespace App\Enums;

enum AlertTypeEnum: string
{
    case ACADEMIC_RISK  = 'academic_risk';
    case ATTENDANCE_RISK = 'attendance_risk';

    public function displayName(): string
    {
        return match ($this) {
            self::ACADEMIC_RISK   => 'Risco Académico',
            self::ATTENDANCE_RISK => 'Risco de Frequência',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ACADEMIC_RISK   => 'A projecção de nota final está abaixo do mínimo de aprovação.',
            self::ATTENDANCE_RISK => 'O número de faltas está a aproximar-se ou atingiu o limite permitido.',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

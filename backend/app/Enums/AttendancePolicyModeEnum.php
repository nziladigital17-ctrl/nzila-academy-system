<?php

namespace App\Enums;

enum AttendancePolicyModeEnum: string
{
    case ANGOLA_POR_DISCIPLINA = 'angola_por_disciplina';
    case ESCOLA_PROPRIA        = 'escola_propria';

    public function displayName(): string
    {
        return match ($this) {
            self::ANGOLA_POR_DISCIPLINA => 'Padrão Angola (por disciplina e carga horária)',
            self::ESCOLA_PROPRIA        => 'Política própria da escola',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ANGOLA_POR_DISCIPLINA =>
                'Aplica os limites definidos pelo regulamento nacional angolano: '
                . '3 faltas para 1 tempo/semana, 4 faltas para 2 tempos/semana, '
                . '5 faltas para mais de 2 tempos/semana.',
            self::ESCOLA_PROPRIA =>
                'Permite configurar livremente um limite global de faltas por trimestre, '
                . 'por disciplina ou total das disciplinas, com ou sem contagem de faltas justificadas.',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

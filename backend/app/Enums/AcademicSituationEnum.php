<?php

namespace App\Enums;

enum AcademicSituationEnum: string
{
    case SEM_NOTAS          = 'sem_notas';
    case EM_RISCO_ACADEMICO = 'em_risco_academico';
    case APROVADO           = 'aprovado';
    case REPROVADO_POR_NOTA = 'reprovado_por_nota';
    case EM_RISCO_POR_FALTAS = 'em_risco_por_faltas';
    case RETIDO_POR_FALTAS  = 'retido_por_faltas';

    public function displayName(): string
    {
        return match ($this) {
            self::SEM_NOTAS           => 'Sem notas',
            self::EM_RISCO_ACADEMICO  => 'Em risco académico',
            self::APROVADO            => 'Aprovado',
            self::REPROVADO_POR_NOTA  => 'Reprovado por nota',
            self::EM_RISCO_POR_FALTAS => 'Em risco por faltas',
            self::RETIDO_POR_FALTAS   => 'Retido por faltas',
        };
    }

    public function isPassing(): bool
    {
        return $this === self::APROVADO;
    }

    public function isFailing(): bool
    {
        return in_array($this, [
            self::REPROVADO_POR_NOTA,
            self::RETIDO_POR_FALTAS,
        ], true);
    }

    public function isAtRisk(): bool
    {
        return in_array($this, [
            self::EM_RISCO_ACADEMICO,
            self::EM_RISCO_POR_FALTAS,
        ], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Enums;

enum AssessmentTypeEnum: string
{
    case AC = 'AC';  // Avaliação Contínua — unlimited per student/subject/term
    case PP = 'PP';  // Prova do Professor — one per student/subject/term
    case PT = 'PT';  // Prova Trimestral   — one per student/subject/term

    public function displayName(): string
    {
        return match ($this) {
            self::AC => 'Avaliação Contínua',
            self::PP => 'Prova do Professor',
            self::PT => 'Prova Trimestral',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AC => 'Avaliações contínuas ao longo do trimestre. Quantidade ilimitada por aluno, disciplina e trimestre.',
            self::PP => 'Prova interna da escola. Uma única nota por aluno, disciplina e trimestre.',
            self::PT => 'Prova trimestral oficial. Uma única nota por aluno, disciplina e trimestre.',
        };
    }

    /**
     * Whether this type allows only one grade per student/subject/term.
     */
    public function isUnique(): bool
    {
        return match ($this) {
            self::AC => false,
            self::PP, self::PT => true,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPER_ADMIN = 'super_admin';
    case SCHOOL_ADMIN = 'school_admin';
    case DIRECTOR = 'director';
    case PEDAGOGIC_COORDINATOR = 'pedagogic_coordinator';
    case FINANCIAL = 'financial';
    case SECRETARY = 'secretary';
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case GUARDIAN = 'guardian';

    /**
     * Get human-readable display name.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrador',
            self::SCHOOL_ADMIN => 'Administrador da Escola',
            self::DIRECTOR => 'Diretor',
            self::PEDAGOGIC_COORDINATOR => 'Coordenador Pedagógico',
            self::FINANCIAL => 'Financeiro',
            self::SECRETARY => 'Secretária',
            self::TEACHER => 'Professor',
            self::STUDENT => 'Aluno',
            self::GUARDIAN => 'Encarregado de Educação',
        };
    }

    /**
     * Get description for this role.
     */
    public function description(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Acesso total ao sistema, gestão de todas as escolas',
            self::SCHOOL_ADMIN => 'Gestão completa de uma escola específica',
            self::DIRECTOR => 'Supervisão académica e administrativa da escola',
            self::PEDAGOGIC_COORDINATOR => 'Coordenação pedagógica, turmas e avaliações',
            self::FINANCIAL => 'Gestão financeira, propinas e despesas',
            self::SECRETARY => 'Gestão de matrículas, alunos e documentação',
            self::TEACHER => 'Gestão de turmas, notas e presenças atribuídas',
            self::STUDENT => 'Acesso aos próprios dados académicos',
            self::GUARDIAN => 'Acompanhamento dos educandos',
        };
    }

    /**
     * Check if this role is a system-level role (not tied to a school).
     */
    public function isSystemRole(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    /**
     * Get all role values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

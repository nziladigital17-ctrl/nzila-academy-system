<?php

namespace App\Enums;

enum GradeBookStatusEnum: string
{
    case DRAFT     = 'draft';
    case SUBMITTED = 'submitted';
    case PUBLISHED = 'published';
    case LOCKED    = 'locked';

    public function displayName(): string
    {
        return match ($this) {
            self::DRAFT     => 'Rascunho',
            self::SUBMITTED => 'Submetido',
            self::PUBLISHED => 'Publicado',
            self::LOCKED    => 'Bloqueado',
        };
    }

    /**
     * Allowed transitions from this status.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT     => [self::SUBMITTED],
            self::SUBMITTED => [self::PUBLISHED, self::DRAFT],
            self::PUBLISHED => [self::LOCKED],
            self::LOCKED    => [self::PUBLISHED],  // unlock → published
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Whether grades can still be edited in this state.
     */
    public function gradesEditable(): bool
    {
        return $this === self::DRAFT;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

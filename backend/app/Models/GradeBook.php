<?php

namespace App\Models;

use App\Enums\GradeBookStatusEnum;
use App\Traits\Auditable;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeBook extends Model
{
    use HasFactory, BelongsToSchool, Auditable;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'term_id',
        'class_id',
        'subject_id',
        'teacher_assignment_id',
        'status',
        'submitted_by',
        'submitted_at',
        'published_by',
        'published_at',
        'locked_by',
        'locked_at',
        'unlocked_by',
        'unlocked_at',
        'unlock_reason',
    ];

    protected $casts = [
        'status'       => GradeBookStatusEnum::class,
        'submitted_at' => 'datetime',
        'published_at' => 'datetime',
        'locked_at'    => 'datetime',
        'unlocked_at'  => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacherAssignment(): BelongsTo
    {
        return $this->belongsTo(TeacherAssignment::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function termResults(): HasMany
    {
        return $this->hasMany(TermResult::class);
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function publishedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    // ── Business logic ─────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === GradeBookStatusEnum::DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === GradeBookStatusEnum::PUBLISHED;
    }

    public function isLocked(): bool
    {
        return $this->status === GradeBookStatusEnum::LOCKED;
    }

    public function gradesEditable(): bool
    {
        return $this->status->gradesEditable();
    }

    public function canTransitionTo(GradeBookStatusEnum $target): bool
    {
        return $this->status->canTransitionTo($target);
    }
}

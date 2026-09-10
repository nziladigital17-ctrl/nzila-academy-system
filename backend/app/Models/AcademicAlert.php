<?php

namespace App\Models;

use App\Enums\AlertTypeEnum;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicAlert extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'enrollment_id',
        'subject_id',
        'term_id',
        'academic_year_id',
        'type',
        'context',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected $casts = [
        'type'            => AlertTypeEnum::class,
        'context'         => 'array',
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('is_acknowledged', false);
    }

    public function scopeOfType($query, AlertTypeEnum $type)
    {
        return $query->where('type', $type->value);
    }
}

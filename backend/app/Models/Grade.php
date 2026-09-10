<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'assessment_id',
        'enrollment_id',
        'grade_book_id',
        'score',
        'remarks',
        'graded_by',
        'is_annulled',
        'annulled_reason',
        'annulled_by',
        'annulled_at',
    ];

    protected $casts = [
        'score'       => 'decimal:2',
        'is_annulled' => 'boolean',
        'annulled_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function gradeBook(): BelongsTo
    {
        return $this->belongsTo(GradeBook::class);
    }

    public function gradedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function annulledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'annulled_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_annulled', false);
    }
}

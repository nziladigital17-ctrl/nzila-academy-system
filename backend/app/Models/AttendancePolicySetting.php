<?php

namespace App\Models;

use App\Enums\AttendancePolicyModeEnum;
use App\Traits\Auditable;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePolicySetting extends Model
{
    use HasFactory, BelongsToSchool, Auditable;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'mode',
        'angola_limits',
        'custom_settings',
        'alert_threshold',
    ];

    protected $casts = [
        'mode'            => AttendancePolicyModeEnum::class,
        'angola_limits'   => 'array',
        'custom_settings' => 'array',
        'alert_threshold' => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * Get the absence limit for a given number of weekly periods (Angola mode).
     * Default Angola limits: 1 period → 3 absences, 2 → 4, 3+ → 5.
     */
    public function getAngolaLimit(int $weeklyPeriods): int
    {
        $limits = $this->angola_limits ?? ['1' => 3, '2' => 4, '3+' => 5];

        if ($weeklyPeriods <= 1) {
            return (int) ($limits['1'] ?? 3);
        }
        if ($weeklyPeriods === 2) {
            return (int) ($limits['2'] ?? 4);
        }
        return (int) ($limits['3+'] ?? 5);
    }

    /**
     * Whether this policy counts justified absences toward the limit.
     */
    public function countsJustified(): bool
    {
        if ($this->mode === AttendancePolicyModeEnum::ANGOLA_POR_DISCIPLINA) {
            // Angola standard: only unjustified absences count
            return false;
        }
        $custom = $this->custom_settings ?? [];
        return (bool) ($custom['count_justified'] ?? false);
    }
}

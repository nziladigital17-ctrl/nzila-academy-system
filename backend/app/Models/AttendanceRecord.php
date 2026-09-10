<?php

namespace App\Models;

use App\Enums\AttendanceStatusEnum;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'attendance_session_id',
        'enrollment_id',
        'status',
        'justification',
    ];

    protected $casts = [
        'status' => AttendanceStatusEnum::class,
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isPresent(): bool
    {
        return $this->status === AttendanceStatusEnum::PRESENT;
    }

    public function isAbsent(): bool
    {
        return $this->status->isAbsence();
    }

    public function isUnjustifiedAbsence(): bool
    {
        return $this->status === AttendanceStatusEnum::ABSENT_UNJUSTIFIED;
    }

    public function isJustifiedAbsence(): bool
    {
        return $this->status === AttendanceStatusEnum::ABSENT_JUSTIFIED;
    }
}

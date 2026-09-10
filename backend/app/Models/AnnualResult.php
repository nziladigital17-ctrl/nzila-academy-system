<?php

namespace App\Models;

use App\Enums\AcademicSituationEnum;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualResult extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'enrollment_id',
        'subject_id',
        'mfa',
        'total_presences',
        'total_absences_justified',
        'total_absences_unjustified',
        'situation',
        'calculated_at',
    ];

    protected $casts = [
        'mfa'                       => 'decimal:2',
        'total_presences'           => 'integer',
        'total_absences_justified'  => 'integer',
        'total_absences_unjustified'=> 'integer',
        'situation'                 => AcademicSituationEnum::class,
        'calculated_at'             => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}

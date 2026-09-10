<?php

namespace App\Models;

use App\Enums\AcademicSituationEnum;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermResult extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'term_id',
        'enrollment_id',
        'subject_id',
        'grade_book_id',
        'mac',
        'pp',
        'pt',
        'nf',
        'presences',
        'absences_justified',
        'absences_unjustified',
        'attendance_percentage',
        'situation',
        'calculated_at',
    ];

    protected $casts = [
        'mac'                  => 'decimal:2',
        'pp'                   => 'decimal:2',
        'pt'                   => 'decimal:2',
        'nf'                   => 'decimal:2',
        'presences'            => 'integer',
        'absences_justified'   => 'integer',
        'absences_unjustified' => 'integer',
        'attendance_percentage'=> 'decimal:2',
        'situation'            => AcademicSituationEnum::class,
        'calculated_at'        => 'datetime',
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

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function gradeBook(): BelongsTo
    {
        return $this->belongsTo(GradeBook::class);
    }
}

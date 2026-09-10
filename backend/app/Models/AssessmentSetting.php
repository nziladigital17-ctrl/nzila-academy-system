<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentSetting extends Model
{
    use HasFactory, BelongsToSchool, Auditable;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'min_grade',
        'max_grade',
        'passing_grade',
        'num_terms',
        'active_components',
        'nf_formula',
        'mfa_formula',
        'rounding_mode',
        'pp_active',
    ];

    protected $casts = [
        'min_grade'          => 'decimal:2',
        'max_grade'          => 'decimal:2',
        'passing_grade'      => 'decimal:2',
        'num_terms'          => 'integer',
        'active_components'  => 'array',
        'pp_active'          => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}

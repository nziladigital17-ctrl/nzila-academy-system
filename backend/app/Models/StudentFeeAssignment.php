<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeeAssignment extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'tuition_plan_id',
        'academic_year_id',
        'discount_percent',
        'discount_fixed',
        'scholarship_type',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'discount_fixed' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tuitionPlan(): BelongsTo
    {
        return $this->belongsTo(TuitionPlan::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function effectiveAmount(): float
    {
        $plan = $this->tuitionPlan;
        if (!$plan) {
            return 0;
        }
        
        $base = (float) $plan->amount;
        $discountFixed = (float) $this->discount_fixed;
        $discountPercent = (float) $this->discount_percent;

        $amount = $base - $discountFixed;
        if ($discountPercent > 0) {
            $amount = $amount - ($amount * ($discountPercent / 100));
        }

        return max(0, $amount);
    }
}


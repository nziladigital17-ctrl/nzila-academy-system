<?php
namespace App\Models;
use App\Traits\Auditable;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TuitionPlan extends Model
{
    use HasFactory, BelongsToSchool, Auditable;
    public static array $periodicities = ['monthly', 'trimestral', 'annual', 'single'];
    protected $fillable = [
        'school_id', 'academic_year_id', 'code', 'name', 'grade_level', 'amount', 
        'periodicity', 'installments', 'due_day', 'is_active', 'description'
    ];
    protected $casts = [
        'amount' => 'decimal:2', 'is_active' => 'boolean'
    ];
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function assignments() { return $this->hasMany(StudentFeeAssignment::class, 'tuition_plan_id'); }
}




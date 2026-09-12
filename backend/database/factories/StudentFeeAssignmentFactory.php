<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFeeAssignmentFactory extends Factory
{
    protected $model = StudentFeeAssignment::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'student_id' => Student::factory(),
            'tuition_plan_id' => 1,
            'academic_year_id' => 1,
            'tuition_plan_id' => \App\Models\TuitionPlan::factory(),
            'academic_year_id' => \App\Models\AcademicYear::factory(),
            'discount_percent' => 0,
            'discount_fixed' => 0,
            'is_active' => true,
        ];
    }
}

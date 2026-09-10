<?php

namespace Database\Factories;

use App\Enums\GradeBookStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class GradeBookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => \App\Models\School::factory(),
            'academic_year_id' => \App\Models\AcademicYear::factory(),
            'term_id' => \App\Models\Term::factory(),
            'class_id' => \App\Models\SchoolClass::factory(),
            'subject_id' => \App\Models\Subject::factory(),
            'teacher_assignment_id' => \App\Models\TeacherAssignment::factory(),
            'status' => GradeBookStatusEnum::DRAFT->value,
            'submitted_at' => null,
            'published_at' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherAssignment>
 */
class TeacherAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => \App\Models\School::factory(),
            'teacher_id' => \App\Models\Teacher::factory(),
            'subject_id' => \App\Models\Subject::factory(),
            'class_id' => \App\Models\SchoolClass::factory(),
            'academic_year_id' => \App\Models\AcademicYear::factory(),
            'role' => 'titular',
        ];
    }
}

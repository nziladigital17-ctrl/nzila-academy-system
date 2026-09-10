<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolClass>
 */
class SchoolClassFactory extends Factory
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
            'academic_year_id' => \App\Models\AcademicYear::factory(),
            'room_id' => \App\Models\Room::factory(),
            'name' => '10ª A',
            'grade_level' => '10ª classe',
            'shift' => 'morning',
            'max_students' => 40,
        ];
    }
}

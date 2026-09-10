<?php

namespace Database\Factories;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'school_id' => \App\Models\School::factory(),
            'student_id' => \App\Models\Student::factory(),
            'class_id' => \App\Models\SchoolClass::factory(),
            'enrolled_at' => $this->faker->date(),
            'status' => $this->faker->randomElement(['active', 'cancelled', 'completed']),
        ];
    }
}

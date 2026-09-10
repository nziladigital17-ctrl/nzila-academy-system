<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assessment_id' => \App\Models\Assessment::factory(),
            'enrollment_id' => \App\Models\Enrollment::factory(),
            'grade_book_id' => \App\Models\GradeBook::factory(),
            'score' => $this->faker->randomFloat(2, 0, 20),
            'remarks' => $this->faker->sentence(),
            'graded_by' => \App\Models\User::factory(),
            'is_annulled' => false,
            'annulled_reason' => null,
            'annulled_at' => null,
            'annulled_by' => null,
        ];
    }
}

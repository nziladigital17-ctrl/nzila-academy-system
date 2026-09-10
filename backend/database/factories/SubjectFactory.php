<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
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
            'name' => $this->faker->word(),
            'code' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'weekly_hours' => 4,
            'is_active' => true,
        ];
    }
}

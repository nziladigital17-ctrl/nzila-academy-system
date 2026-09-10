<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assessment>
 */
class AssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => \App\Models\SchoolClass::factory(),
            'term_id' => \App\Models\Term::factory(),
            'name' => 'Avaliação ' . $this->faker->word(),
            'type' => $this->faker->randomElement(['exam', 'test', 'assignment', 'participation']),
            'date' => $this->faker->date(),
            'max_score' => 20.0,
            'weight' => 1.0,
        ];
    }
}

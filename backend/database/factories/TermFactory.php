<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Term>
 */
class TermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => \App\Models\AcademicYear::factory(),
            'name' => $this->faker->unique()->numberBetween(1, 3) . 'º Trimestre',
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
        ];
    }
}

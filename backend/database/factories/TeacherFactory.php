<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
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
            'user_id' => \App\Models\User::factory(),
            'employee_number' => $this->faker->unique()->numerify('EMP-####'),
            'full_name' => $this->faker->name(),
            'specialization' => $this->faker->word(),
            'academic_degree' => $this->faker->word(),
            'phone' => $this->faker->phoneNumber(),
            'hire_date' => $this->faker->date(),
            'is_active' => true,
        ];
    }
}

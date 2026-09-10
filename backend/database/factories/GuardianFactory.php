<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guardian>
 */
class GuardianFactory extends Factory
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
            'full_name' => $this->faker->name(),
            'relationship' => $this->faker->randomElement(['pai', 'mae', 'tutor', 'outro']),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'bi_number' => $this->faker->unique()->numerify('##########LA###'),
            'occupation' => $this->faker->jobTitle(),
            'address' => $this->faker->address(),
        ];
    }
}

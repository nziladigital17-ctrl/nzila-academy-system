<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'student_number' => 'ALU-' . now()->format('Y') . '-' . fake()->unique()->numerify('#####'),
            'gender' => fake()->randomElement(['M', 'F']),
            'birth_date' => fake()->dateTimeBetween('-18 years', '-10 years'),
            'birth_place' => fake()->city(),
            'nationality' => 'Angolana',
            'bi_number' => fake()->unique()->numerify('##########LA###'),
            'is_active' => true,
        ];
    }
}

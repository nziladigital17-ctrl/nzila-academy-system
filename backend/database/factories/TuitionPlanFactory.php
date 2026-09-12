<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\TuitionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class TuitionPlanFactory extends Factory
{
    protected $model = TuitionPlan::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'academic_year_id' => 1,
            'academic_year_id' => \App\Models\AcademicYear::factory(),
            'code' => 'PROP-' . fake()->unique()->numerify('####'),
            'name' => fake()->randomElement(['Propina Mensal 7ª Classe', 'Propina Mensal 8ª Classe']),
            'grade_level' => fake()->randomElement(['7ª', '8ª']),
            'amount' => fake()->randomFloat(2, 5000, 50000),
            'installments' => fake()->numberBetween(1, 12),
            'periodicity' => fake()->randomElement(TuitionPlan::$periodicities),
            'due_day' => 10,
            'is_active' => true,
        ];
    }
}

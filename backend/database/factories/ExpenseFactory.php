<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'category' => fake()->randomElement(['Material Escolar', 'Salários', 'Manutenção', 'Transporte', 'Utilidades']),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 1000, 100000),
            'date' => fake()->dateTimeThisYear()->format('Y-m-d'),
            'status' => 'draft',
        ];
    }
}

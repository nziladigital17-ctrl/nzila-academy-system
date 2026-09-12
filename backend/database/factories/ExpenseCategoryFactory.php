<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->randomElement(['Material Escolar', 'Salários', 'Manutenção', 'Transporte', 'Utilidades']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}

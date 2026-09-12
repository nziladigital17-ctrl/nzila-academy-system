<?php

namespace Database\Factories;

use App\Models\FinancialAdjustment;
use App\Models\Invoice;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialAdjustmentFactory extends Factory
{
    protected $model = FinancialAdjustment::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'invoice_id' => Invoice::factory(),
            'type' => fake()->randomElement(FinancialAdjustment::TYPES),
            'amount' => fake()->randomFloat(2, 500, 5000),
            'description' => fake()->sentence(),
            'applied_by' => 1,
        ];
    }
}

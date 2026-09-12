<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinancialSetting>
 */
class FinancialSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'school_id' => \App\Models\School::factory(),
            'academic_year_id' => \App\Models\AcademicYear::factory(),
            'setting_key' => 'currency',
            'setting_value' => 'AOA',
        ];
    }
}

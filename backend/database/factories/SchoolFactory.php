<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return [
            'name' => 'Escola ' . fake()->company(),
            'code' => strtoupper(fake()->unique()->bothify('SCH-####')),
            'nif' => fake()->unique()->numerify('5#########'),
            'address' => fake()->address(),
            'province' => fake()->randomElement([
                'Luanda', 'Benguela', 'Huambo', 'Huíla', 'Cabinda',
                'Malanje', 'Uíge', 'Kwanza Sul', 'Lunda Norte', 'Bié',
            ]),
            'phone' => '+244 9' . fake()->numerify('## ### ###'),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
        ];
    }
}
